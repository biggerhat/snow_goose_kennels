<?php

use App\Mail\NewApplication;
use App\Models\Contact;
use App\Models\Dog;
use App\Models\Litter;
use App\Models\Setting;
use App\Models\User;
use App\Models\WaitlistEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('a visitor can apply and it lands in the waitlist as a new application', function () {
    Mail::fake();
    User::factory()->create();
    $litter = Litter::create(['status' => 'expected', 'name' => 'Spring', 'is_public' => true]);

    $this->post(route('site.apply.store'), [
        'name' => 'Jane Miller', 'email' => 'jane@example.com', 'phone' => '555-0101',
        'litter_id' => $litter->id, 'preferred_sex' => 'female', 'home' => 'Fenced yard, two kids',
    ])->assertRedirect(route('site.apply'));

    $entry = WaitlistEntry::first();
    expect($entry->status->value)->toBe('new')
        ->and($entry->source)->toBe('website')
        ->and($entry->application['Home and family'])->toBe('Fenced yard, two kids')
        ->and($entry->contact->name)->toBe('Jane Miller')
        ->and($entry->contact->contactNotes()->count())->toBe(1);

    Mail::assertSent(NewApplication::class);
});

test('applying again with the same email reuses the contact', function () {
    Mail::fake();
    $payload = ['name' => 'Jane', 'email' => 'jane@example.com'];

    $this->post(route('site.apply.store'), $payload);
    $this->post(route('site.apply.store'), $payload);

    expect(Contact::count())->toBe(1)->and(WaitlistEntry::count())->toBe(2);
});

test('the honeypot silently drops bot submissions', function () {
    Mail::fake();

    $this->post(route('site.apply.store'), ['name' => 'Bot', 'email' => 'bot@example.com', 'website' => 'http://spam'])
        ->assertRedirect(route('site.apply'));

    expect(WaitlistEntry::count())->toBe(0)->and(Contact::count())->toBe(0);
    Mail::assertNothingSent();
});

test('an application needs a name and a way to reply, and cannot target hidden litters', function () {
    $hidden = Litter::create(['status' => 'planned', 'is_public' => false]);

    $this->post(route('site.apply.store'), ['name' => 'No Contact'])->assertSessionHasErrors(['email', 'phone']);
    $this->post(route('site.apply.store'), ['name' => 'X', 'email' => 'x@example.com', 'litter_id' => $hidden->id])
        ->assertSessionHasErrors('litter_id');
});

test('application answers never appear on the public site', function () {
    Mail::fake();
    $this->post(route('site.apply.store'), ['name' => 'Secret Buyer', 'email' => 's@example.com', 'message' => 'APPLICATION-SECRET']);

    foreach (['home', 'site.litters', 'site.dogs', 'site.apply', 'site.contact'] as $r) {
        expect($this->get(route($r))->getContent())->not->toContain('APPLICATION-SECRET')->not->toContain('Secret Buyer');
    }
});

test('how it works shows only the sections the owner has filled in', function () {
    $this->get(route('site.contact'))->assertOk();

    $this->actingAs(User::factory()->create())
        ->put(route('admin.site.update'), ['phone' => '555-1234', 'deposit_policy' => 'Deposits are $300 and non-refundable.'])
        ->assertRedirect();

    expect(Setting::get('deposit_policy'))->toBe('Deposits are $300 and non-refundable.');

    $html = $this->get(route('site.contact'))->getContent();
    expect($html)->toContain('Deposits are $300')->not->toContain('Refunds and changes\\u0022,\\u0022body');
    $this->get(route('site.contact'))->assertSee('555-1234', false);
});

test('health tests keep an OFA number and a verification link, and only http links are accepted', function () {
    $this->actingAs(User::factory()->create());
    $dog = Dog::create(['name' => 'Bella', 'sex' => 'female', 'role' => 'dam']);

    $this->post(route('admin.health-tests.store', $dog), ['name' => 'OFA Hips', 'result' => 'Good', 'certificate_number' => 'LR-1G24F', 'verification_url' => 'https://ofa.org/x'])->assertRedirect();
    $this->post(route('admin.health-tests.store', $dog), ['name' => 'EIC', 'result' => 'Clear', 'verification_url' => 'javascript:alert(1)'])->assertSessionHasErrors('verification_url');

    $html = $this->get(route('site.dog', $dog))->getContent();
    expect($html)->toContain('LR-1G24F')->toContain('https:\\/\\/ofa.org\\/x');
});

test('litters expose a last-updated date that moves when a puppy changes', function () {
    $litter = Litter::create(['status' => 'on_ground', 'is_public' => true]);
    $this->travel(2)->days();
    $litter->puppies()->create(['sex' => 'male', 'status' => 'available']);

    expect($litter->fresh()->updated_at->isToday())->toBeTrue();
    $this->get(route('site.litters'))->assertSee('updated_on', false);
});

test('sitemap lists public pages and dogs but not hidden dogs', function () {
    Dog::create(['name' => 'Visible', 'sex' => 'male', 'role' => 'stud', 'is_public' => true]);
    Dog::create(['name' => 'Hidden', 'sex' => 'male', 'role' => 'stud', 'is_public' => false]);

    $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
    expect($xml)->toContain('/our-dogs/visible')->not->toContain('hidden')->toContain('/apply');
});
