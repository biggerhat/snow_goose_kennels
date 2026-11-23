<?php

use App\Models\Contact;
use App\Models\Dog;
use App\Models\Litter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs(User::factory()->create()));

function found(string $q): array
{
    return collect(test()->getJson(route('admin.search', ['q' => $q]))->assertOk()->json('groups'))->mapWithKeys(fn ($g) => [$g['title'] => collect($g['items'])->pluck('label')->all()])->all();
}

test('search finds dogs by name, registered name, number and microchip', function () {
    Dog::create(['name' => 'Bella', 'registered_name' => 'Snow Goose Bella of the Marsh', 'sex' => 'female', 'role' => 'dam', 'registry_number' => 'SR123456', 'microchip' => '985112345678901']);

    foreach (['bell', 'Marsh', 'SR1234', '98511234'] as $q) {
        expect(found($q)['Dogs'])->toBe(['Bella']);
    }
});

test('search finds litters by parent, puppies by collar, and contacts by phone or email', function () {
    $sire = Dog::create(['name' => 'Duke', 'sex' => 'male', 'role' => 'stud']);
    $dam = Dog::create(['name' => 'Honey', 'sex' => 'female', 'role' => 'dam']);
    $litter = Litter::create(['status' => 'on_ground', 'born_on' => today()->subWeek(), 'sire_id' => $sire->id, 'dam_id' => $dam->id]);
    $litter->puppies()->create(['sex' => 'male', 'status' => 'available', 'collar' => 'Turquoise']);
    Contact::create(['name' => 'Jane Miller', 'type' => 'buyer', 'phone' => '(208) 555-0142', 'email' => 'jane@example.com']);

    expect(found('Duke')['Litters'])->toBe(['Honey × Duke'])
        ->and(found('turq')['Puppies'])->toBe(['Turquoise collar puppy'])
        ->and(found('555-0142')['Contacts'])->toBe(['Jane Miller'])
        ->and(found('jane@')['Contacts'])->toBe(['Jane Miller']);
});

test('archived dogs are found and marked', function () {
    $dog = Dog::create(['name' => 'Granddad', 'sex' => 'male', 'role' => 'stud']);
    $dog->delete();

    $items = $this->getJson(route('admin.search', ['q' => 'Grand']))->json('groups.0.items');

    expect($items[0]['label'])->toBe('Granddad')->and($items[0]['sub'])->toContain('archived');
});

test('a short or empty search returns nothing, and wildcards are taken literally', function () {
    Dog::create(['name' => 'Bella', 'sex' => 'female', 'role' => 'dam']);

    expect($this->getJson(route('admin.search', ['q' => 'b']))->json('groups'))->toBe([])
        ->and($this->getJson(route('admin.search', ['q' => '%%']))->json('groups'))->toBe([]);
});

test('search is for the owner only', function () {
    auth()->logout();

    $this->getJson(route('admin.search', ['q' => 'bella']))->assertUnauthorized();
});
