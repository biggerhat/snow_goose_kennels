<?php

use App\Models\Contact;
use App\Models\Dog;
use App\Models\Litter;
use App\Models\User;
use App\Support\AkcChecks;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function dogAged(string $name, string $sex, string $born, array $over = []): Dog
{
    return Dog::create($over + ['name' => $name, 'sex' => $sex, 'role' => $sex === 'male' ? 'stud' : 'dam', 'birth_date' => $born, 'registry_number' => 'SR'.random_int(100000, 999999)]);
}

function status(array $result, string $key): ?string
{
    return collect($result['items'])->firstWhere('key', $key)['status'] ?? null;
}

test('the parents need registration numbers', function () {
    $sire = dogAged('Duke', 'male', '2022-01-01', ['registry_number' => null]);
    $dam = dogAged('Bella', 'female', '2022-01-01');
    $litter = Litter::create(['status' => 'expected', 'sire_id' => $sire->id, 'dam_id' => $dam->id]);

    expect(status(AkcChecks::forLitter($litter), 'parents'))->toBe('todo');
    $sire->update(['registry_number' => 'SR123']);
    expect(status(AkcChecks::forLitter($litter->fresh()), 'parents'))->toBe('ok');
});

test('the dam and sire ages at mating are checked against AKC limits', function () {
    $young = dogAged('Pup', 'female', '2026-01-01');
    $sire = dogAged('Duke', 'male', '2022-01-01');
    $litter = Litter::create(['status' => 'expected', 'sire_id' => $sire->id, 'dam_id' => $young->id, 'bred_on' => '2026-06-01']);

    $r = AkcChecks::forLitter($litter);
    expect(status($r, 'dam_age'))->toBe('warn')->and(status($r, 'sire_age'))->toBe('ok');

    $old = dogAged('Granny', 'female', '2012-01-01');
    $litter->update(['dam_id' => $old->id]);
    expect(status(AkcChecks::forLitter($litter->fresh()), 'dam_age'))->toBe('warn');

    $fine = dogAged('Prime', 'female', '2023-01-01');
    $litter->update(['dam_id' => $fine->id]);
    expect(status(AkcChecks::forLitter($litter->fresh()), 'dam_age'))->toBe('ok');
});

test('the 6 month litter application deadline is tracked', function () {
    $litter = Litter::create(['status' => 'on_ground', 'born_on' => today()->subMonths(5)->subDays(10)]);
    expect(status(AkcChecks::forLitter($litter), 'applied'))->toBe('warn');

    $litter->update(['born_on' => today()->subMonths(1)]);
    expect(status(AkcChecks::forLitter($litter->fresh()), 'applied'))->toBe('todo');

    $litter->update(['born_on' => today()->subMonths(8)]);
    $r = AkcChecks::forLitter($litter->fresh());
    expect(status($r, 'applied'))->toBe('warn')->and(collect($r['items'])->firstWhere('key', 'applied')['title'])->toContain('passed');

    $litter->update(['akc_applied_on' => today()->subMonths(7)]);
    $r = AkcChecks::forLitter($litter->fresh());
    expect(status($r, 'applied'))->toBe('ok')->and(status($r, 'number'))->toBe('todo');

    $litter->update(['akc_litter_number' => 'NP12345']);
    expect(status(AkcChecks::forLitter($litter->fresh()), 'number'))->toBe('ok');
});

test('a frequently used sire needs a DNA profile', function () {
    $sire = dogAged('Duke', 'male', '2020-01-01');
    $dam = dogAged('Bella', 'female', '2021-01-01');
    foreach (range(1, 4) as $i) {
        $litter = Litter::create(['status' => 'expected', 'sire_id' => $sire->id, 'dam_id' => $dam->id, 'born_on' => today()->subMonths(2)->subDays($i)]);
    }

    expect(status(AkcChecks::forLitter($litter), 'dna'))->toBe('warn');

    $sire->update(['dna_profile' => 'V123456']);
    expect(status(AkcChecks::forLitter($litter->fresh()), 'dna'))->toBe('ok');
});

test('artificial insemination and conditional registration also call for DNA', function () {
    $sire = dogAged('Duke', 'male', '2020-01-01');
    $dam = dogAged('Bella', 'female', '2021-01-01', ['registry_number' => 'Q12345']);

    $ai = Litter::create(['status' => 'expected', 'sire_id' => $sire->id, 'dam_id' => $dam->id, 'breeding_method' => 'ai_fresh']);
    expect(status(AkcChecks::forLitter($ai), 'dna'))->toBe('warn');

    $natural = Litter::create(['status' => 'expected', 'sire_id' => $sire->id, 'dam_id' => $dam->id, 'breeding_method' => 'natural']);
    expect(status(AkcChecks::forLitter($natural), 'dna'))->toBe('warn'); // dam is Q
});

test('sold puppies need a buyer with an address and recorded papers', function () {
    $litter = Litter::create(['status' => 'on_ground', 'born_on' => today()->subWeeks(10)]);
    $buyer = Contact::create(['name' => 'Jane', 'type' => 'buyer']);
    $litter->puppies()->create(['sex' => 'male', 'status' => 'home', 'contact_id' => $buyer->id]);
    $litter->puppies()->create(['sex' => 'female', 'status' => 'reserved']);

    $r = AkcChecks::forLitter($litter->fresh());
    expect(status($r, 'buyers'))->toBe('todo')->and(status($r, 'papers'))->toBe('todo');

    $buyer->update(['address' => '1 Main', 'city' => 'Boise', 'state' => 'ID']);
    $litter->puppies()->where('status', 'reserved')->update(['contact_id' => $buyer->id]);
    $litter->puppies()->where('status', 'home')->update(['registration_type' => 'full', 'papers_given_on' => today()]);

    $r = AkcChecks::forLitter(Litter::find($litter->id));
    expect(status($r, 'buyers'))->toBe('ok')->and(status($r, 'papers'))->toBe('ok');
});

test('a dog record shows what is missing', function () {
    $dog = Dog::create(['name' => 'Bella', 'sex' => 'female', 'role' => 'dam']);
    $r = AkcChecks::forDog($dog);
    expect($r['todo'])->toBeGreaterThanOrEqual(3);

    $dog->update(['birth_date' => '2022-01-01', 'registry_number' => 'SR1', 'microchip' => '985112345678901', 'acquired_on' => '2022-03-01']);
    expect(AkcChecks::forDog($dog->fresh())['todo'])->toBe(0);
});

test('breeding details validate and accept typed money', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('admin.litters.store'), ['status' => 'planned', 'stud_fee' => '$1,200', 'breeding_method' => 'natural', 'bred_place' => 'Bend, OR'])->assertSessionHasNoErrors();
    expect(Litter::first()->stud_fee)->toBe(1200);

    $this->post(route('admin.litters.store'), ['status' => 'on_ground', 'born_on' => '2026-05-01', 'akc_applied_on' => '2026-04-01'])->assertSessionHasErrors('akc_applied_on');
    $this->post(route('admin.litters.store'), ['status' => 'planned', 'breeding_method' => 'magic'])->assertSessionHasErrors('breeding_method');
});

test('puppy go-home details validate the microchip and dates', function () {
    $this->actingAs(User::factory()->create());
    $litter = Litter::create(['status' => 'on_ground', 'born_on' => today()->subWeeks(9)]);
    $puppy = $litter->puppies()->create(['sex' => 'male', 'status' => 'available']);
    $base = ['sex' => 'male', 'status' => 'home'];

    $this->put(route('admin.puppies.update', $puppy), $base + ['microchip' => 'abc'])->assertSessionHasErrors('microchip');
    $this->put(route('admin.puppies.update', $puppy), $base + ['went_home_on' => today()->addDay()->toDateString()])->assertSessionHasErrors('went_home_on');
    $this->put(route('admin.puppies.update', $puppy), $base + ['microchip' => '985 112 345 678 901', 'registration_type' => 'limited', 'papers_given_on' => today()->toDateString()])->assertSessionHasNoErrors();

    expect($puppy->fresh()->microchip)->toBe('985112345678901')->and($puppy->fresh()->registration_type->value)->toBe('limited');
});

test('signatures, multiple sires and artificial insemination are tracked', function () {
    $owner = Contact::create(['name' => 'Ridgeline', 'type' => 'stud_owner']);
    $sire = dogAged('Duke', 'male', '2020-01-01', ['is_ours' => false, 'owner_contact_id' => $owner->id]);
    $dam = dogAged('Bella', 'female', '2021-01-01', ['co_owner_name' => 'Pat Lee']);
    $litter = Litter::create(['status' => 'on_ground', 'born_on' => today()->subMonths(1), 'sire_id' => $sire->id, 'dam_id' => $dam->id, 'breeding_method' => 'ai_fresh', 'multiple_sires' => true]);

    $r = AkcChecks::forLitter($litter);
    expect(status($r, 'signed'))->toBe('todo')->and(status($r, 'multi'))->toBe('warn')->and(status($r, 'ai'))->toBe('todo')->and(status($r, 'coowner'))->toBe('info');
    expect(collect($r['items'])->firstWhere('key', 'signed')['detail'])->toContain('Ridgeline')->toContain('Pat Lee');

    $litter->update(['sire_owner_signed_on' => today(), 'dam_owner_signed_on' => today()]);
    expect(status(AkcChecks::forLitter($litter->fresh()), 'signed'))->toBe('ok');
});
