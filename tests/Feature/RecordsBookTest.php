<?php

use App\Models\Contact;
use App\Models\Dog;
use App\Models\Litter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs(User::factory()->create()));

function seedKennel(): array
{
    $owner = Contact::create(['name' => 'Ridgeline', 'type' => 'stud_owner', 'city' => 'Bend', 'state' => 'OR']);
    $sire = Dog::create(['name' => 'Duke', 'sex' => 'male', 'role' => 'other', 'is_ours' => false, 'owner_contact_id' => $owner->id, 'registry_number' => 'SR-DUKE-1', 'dna_profile' => 'V99999']);
    $dam = Dog::create(['name' => 'Bella', 'sex' => 'female', 'role' => 'dam', 'registry_number' => 'SR-BELLA-1', 'microchip' => '985112345678901', 'birth_date' => '2022-03-01', 'titles' => 'MH']);
    $dam->healthRecords()->create(['type' => 'vaccine', 'name' => 'Rabies', 'given_on' => '2026-01-10', 'next_due_on' => '2027-01-10']);
    $dam->healthTests()->create(['name' => 'OFA Hips', 'result' => 'Good', 'certificate_number' => 'LR-123']);
    $buyer = Contact::create(['name' => 'Jane Miller', 'type' => 'buyer', 'address' => '12 Elm St', 'city' => 'Boise', 'state' => 'ID', 'postal_code' => '83702', 'phone' => '555-0101']);
    $litter = Litter::create(['status' => 'on_ground', 'name' => 'Spring Litter', 'sire_id' => $sire->id, 'dam_id' => $dam->id, 'bred_on' => '2026-02-01', 'born_on' => '2026-04-05', 'breeding_method' => 'natural', 'bred_place' => 'Bend, OR', 'stud_fee' => 800, 'akc_litter_number' => 'NP-555']);
    $litter->puppies()->create(['sex' => 'male', 'status' => 'home', 'collar' => 'Red', 'contact_id' => $buyer->id, 'microchip' => '985000000000042', 'registration_type' => 'full', 'went_home_on' => '2026-06-01']);

    return [$sire, $dam, $litter];
}

test('the records page lists dogs and litters', function () {
    seedKennel();

    $props = $this->get(route('admin.records'))->assertOk()->viewData('page')['props'];

    expect($props['dogs'])->toHaveCount(2)->and($props['litters'][0]['title'])->toBe('Spring Litter');
});

test('a dog record prints with identification, ownership, litters and health', function () {
    [, $dam] = seedKennel();

    $html = $this->get(route('admin.records.dog', $dam))->assertOk()->getContent();

    expect($html)->toContain('Dog record: Bella')->toContain('SR-BELLA-1')->toContain('985112345678901')->toContain('Duke')->toContain('SR-DUKE-1')
        ->toContain('Rabies')->toContain('OFA Hips')->toContain('NP-555')->toContain('at least 5 years');
});

test('an outside dog record shows who owns it', function () {
    [$sire] = seedKennel();

    expect($this->get(route('admin.records.dog', $sire))->getContent())->toContain('Ridgeline')->toContain('V99999');
});

test('a litter record prints the buyers, microchips and AKC details', function () {
    [, , $litter] = seedKennel();

    $html = $this->get(route('admin.records.litter', $litter))->assertOk()->getContent();

    expect($html)->toContain('Litter record: Spring Litter')->toContain('Jane Miller')->toContain('12 Elm St')->toContain('985000000000042')
        ->toContain('Natural mating')->toContain('$800')->toContain('NP-555')->toContain('1 males')->toContain('Full registration');
});

test('print everything includes every dog and litter', function () {
    seedKennel();

    $html = $this->get(route('admin.records.all'))->assertOk()->getContent();

    expect($html)->toContain('Dog record: Bella')->toContain('Dog record: Duke')->toContain('Litter record: Spring Litter');
});

test('records are for the owner only', function () {
    [, $dam, $litter] = seedKennel();
    $urls = [route('admin.records'), route('admin.records.all'), route('admin.records.dog', $dam), route('admin.records.litter', $litter)];
    auth()->logout();

    foreach ($urls as $u) {
        $this->get($u)->assertRedirect(route('login'));
    }
});

test('the new spreadsheets download', function () {
    seedKennel();

    foreach (['dogs', 'litters', 'puppies', 'health', 'documents'] as $type) {
        $response = $this->get(route('admin.export', $type))->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('text/csv');
    }

    $csv = $this->get(route('admin.export', 'litters'))->streamedContent();
    expect($csv)->toContain('Spring Litter')->toContain('NP-555')->toContain('Natural mating');
    expect($this->get(route('admin.export', 'puppies'))->streamedContent())->toContain('12 Elm St')->toContain('985000000000042');
});
