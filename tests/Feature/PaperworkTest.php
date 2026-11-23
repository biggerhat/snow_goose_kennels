<?php

use App\Models\Contact;
use App\Models\Dog;
use App\Models\Litter;
use App\Models\Setting;
use App\Models\User;
use App\Models\WaitlistEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->sire = Dog::create(['name' => 'Duke', 'registered_name' => 'Ridgeline Duke', 'sex' => 'male', 'role' => 'stud', 'registry_number' => 'SR1']);
    $this->dam = Dog::create(['name' => 'Bella', 'sex' => 'female', 'role' => 'dam']);
    $this->litter = Litter::create(['sire_id' => $this->sire->id, 'dam_id' => $this->dam->id, 'status' => 'on_ground', 'born_on' => '2026-10-01', 'price' => 1800, 'akc_litter_number' => 'NP999']);
    $this->buyer = Contact::create(['name' => 'Jane Miller', 'type' => 'buyer', 'city' => 'Boise', 'state' => 'ID', 'phone' => '555-0100']);
    $this->puppy = $this->litter->puppies()->create(['name' => 'Sunny', 'sex' => 'female', 'status' => 'reserved', 'contact_id' => $this->buyer->id, 'microchip' => '985112000000101']);
});

it('prints a go-home packet with the puppy, chip and health care', function () {
    $this->puppy->healthRecords()->create(['type' => 'deworming', 'name' => 'Pyrantel', 'given_on' => '2026-10-15', 'next_due_on' => '2026-10-29']);
    Setting::put('packet_food', 'Purina Pro Plan puppy, one cup twice a day');

    $this->get(route('admin.records.packet', $this->puppy))->assertOk()
        ->assertSee('Going home: Sunny')->assertSee('985112000000101')->assertSee('NP999')
        ->assertSee('Pyrantel')->assertSee('Purina Pro Plan puppy')->assertSee('Jane Miller');
});

it('leaves out food and care sections she has not written', function () {
    $this->get(route('admin.records.packet', $this->puppy))->assertOk()->assertDontSee('What Sunny eats')->assertDontSee('The first weeks at home');
});

it('prints a sales agreement with the buyer, price, deposit balance and her own terms only', function () {
    WaitlistEntry::create(['contact_id' => $this->buyer->id, 'litter_id' => $this->litter->id, 'puppy_id' => $this->puppy->id, 'status' => 'deposit_paid', 'deposit_amount' => 300, 'deposit_paid_on' => '2026-09-01']);
    Setting::put('health_guarantee', 'Two years against genetic hip problems.');
    Setting::put('payment_instructions', 'Venmo @snowgoose');

    $this->get(route('admin.records.agreement', $this->puppy))->assertOk()
        ->assertSee('Jane Miller')->assertSee('$1,800', false)->assertSee('$300', false)->assertSee('$1,500', false)
        ->assertSee('Two years against genetic hip problems.')->assertSee('Venmo @snowgoose')
        ->assertSee('not legal advice');
});

it('does not invent terms when none are written', function () {
    $this->get(route('admin.records.agreement', $this->puppy))->assertOk()->assertSee('No terms are written yet')->assertDontSee('Health guarantee');
});

it('prints a deposit receipt', function () {
    $entry = WaitlistEntry::create(['contact_id' => $this->buyer->id, 'litter_id' => $this->litter->id, 'status' => 'deposit_paid', 'deposit_amount' => 250, 'deposit_paid_on' => '2026-09-01', 'pick_order' => 2]);
    Setting::put('deposit_policy', 'Deposits are non-refundable but can move to the next litter.');

    $this->get(route('admin.records.receipt', $entry))->assertOk()
        ->assertSee('Deposit receipt')->assertSee('Jane Miller')->assertSee('$250', false)->assertSee('non-refundable');
});

it('prints a stud agreement naming both owners', function () {
    $owner = Contact::create(['name' => 'Ridgeline Kennel', 'type' => 'stud_owner']);
    $this->sire->update(['is_ours' => false, 'owner_contact_id' => $owner->id]);
    $this->litter->update(['stud_fee' => 900]);
    Setting::put('stud_terms', 'One return breeding if no puppies.');

    $this->get(route('admin.records.stud', $this->litter))->assertOk()
        ->assertSee('Stud service agreement')->assertSee('Ridgeline Kennel')->assertSee('$900', false)->assertSee('One return breeding');
});

it('saves the buyer paper wording from Website text', function () {
    $this->put(route('admin.site.update'), ['packet_food' => 'Kibble', 'stud_terms' => 'Terms here'])->assertRedirect();

    expect(Setting::get('packet_food'))->toBe('Kibble')->and(Setting::get('stud_terms'))->toBe('Terms here');
    $this->get(route('admin.site.edit'))->assertInertia(fn ($p) => $p->has('papers', 6));
});

it('records the whelping log on a puppy and rejects a bad time', function () {
    $base = ['sex' => 'female', 'status' => 'available'];

    $this->put(route('admin.puppies.update', $this->puppy), $base + ['born_time' => '25:99'])->assertSessionHasErrors('born_time');
    $this->put(route('admin.puppies.update', $this->puppy), $base + ['born_time' => '03:20', 'birth_order' => 2, 'birth_notes' => 'Needed a little help']);

    expect($this->puppy->fresh())->born_time->toBe('03:20')->birth_order->toBe(2)->birth_notes->toBe('Needed a little help');
    $this->get(route('admin.records.litter', $this->litter))->assertSee('#2', false)->assertSee('03:20');
});
