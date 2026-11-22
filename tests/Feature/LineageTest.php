<?php

use App\Models\Contact;
use App\Models\Dog;
use App\Models\Litter;
use App\Models\User;
use App\Support\Pedigree;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs(User::factory()->create()));

function mk(string $name, string $sex, array $over = []): Dog
{
    return Dog::create($over + ['name' => $name, 'sex' => $sex, 'role' => $sex === 'male' ? 'stud' : 'dam', 'is_public' => true]);
}

test('a dog from another kennel can never be public', function () {
    $this->post(route('admin.dogs.store'), ['name' => 'Zzyzx', 'sex' => 'male', 'role' => 'stud', 'is_ours' => false, 'is_public' => true])->assertSessionHasNoErrors();

    $rex = Dog::firstWhere('name', 'Zzyzx');
    expect($rex->is_ours)->toBeFalse()->and($rex->is_public)->toBeFalse();
    expect(collect($this->get(route('site.dogs'))->viewData('page')['props']['dogs'])->pluck('name'))->not->toContain('Zzyzx');
});

test('a sire or dam from another kennel can be added on the spot', function () {
    $owner = Contact::create(['name' => 'Ridgeline Retrievers', 'type' => 'stud_owner']);

    $this->post(route('admin.dogs.quick'), ['name' => 'Ridge Duke', 'sex' => 'male', 'registered_name' => 'Ridgeline Duke of Bend', 'registry_number' => 'SR111', 'owner_contact_id' => $owner->id])->assertSessionHasNoErrors();

    $dog = Dog::firstWhere('name', 'Ridge Duke');
    expect($dog->is_ours)->toBeFalse()->and($dog->is_public)->toBeFalse()->and($dog->owner_contact_id)->toBe($owner->id);
    $this->post(route('admin.dogs.quick'), ['name' => 'Ridge Duke', 'sex' => 'male'])->assertSessionHasErrors('name');
});

test('an outside sire can sire one of our litters and shows on the litter card without a link', function () {
    $sire = mk('OutsideSire', 'male', ['is_ours' => false, 'registry_number' => 'SECRET-REG-1']);
    $dam = mk('Bella', 'female');
    Litter::create(['status' => 'expected', 'sire_id' => $sire->id, 'dam_id' => $dam->id, 'is_public' => true]);

    $html = $this->get(route('site.litters'))->getContent();

    expect($html)->toContain('OutsideSire')->not->toContain('SECRET-REG-1')->not->toContain('/our-dogs/outsidesire');
});

test('parents must be the right sex, not the dog itself, and never a descendant', function () {
    $sire = mk('Duke', 'male');
    $dam = mk('Bella', 'female');
    $pup = mk('Hazel', 'female', ['sire_id' => $sire->id, 'dam_id' => $dam->id]);

    $this->put(route('admin.dogs.update', $pup), ['name' => 'Hazel', 'sex' => 'female', 'role' => 'dam', 'sire_id' => $dam->id])->assertSessionHasErrors('sire_id');
    $this->put(route('admin.dogs.update', $pup), ['name' => 'Hazel', 'sex' => 'female', 'role' => 'dam', 'dam_id' => $pup->id])->assertSessionHasErrors('dam_id');
    // Bella cannot take her own daughter as her mother.
    $this->put(route('admin.dogs.update', $dam), ['name' => 'Bella', 'sex' => 'female', 'role' => 'dam', 'dam_id' => $pup->id])->assertSessionHasErrors('dam_id');
    $this->put(route('admin.dogs.update', $pup), ['name' => 'Hazel', 'sex' => 'female', 'role' => 'dam', 'sire_id' => $sire->id, 'dam_id' => $dam->id])->assertSessionHasNoErrors();
});

test('the pedigree builds back through the generations', function () {
    $gsire = mk('Grand', 'male');
    $sire = mk('Duke', 'male', ['sire_id' => $gsire->id]);
    $dam = mk('Bella', 'female');
    $pup = mk('Hazel', 'female', ['sire_id' => $sire->id, 'dam_id' => $dam->id]);

    $tree = Pedigree::build($pup, 3);

    expect($tree['name'])->toBe('Hazel')
        ->and($tree['sire']['name'])->toBe('Duke')
        ->and($tree['sire']['sire']['name'])->toBe('Grand')
        ->and($tree['dam']['name'])->toBe('Bella')
        ->and($tree['dam']['sire'])->toBeNull();
});

test('the public dog page shows the pedigree but never registration numbers or chips', function () {
    $sire = mk('Duke', 'male', ['registry_number' => 'SECRET-REG-2', 'microchip' => '985000000000001', 'titles' => 'MH']);
    $pup = mk('Hazel', 'female', ['sire_id' => $sire->id]);

    $html = $this->get(route('site.dog', $pup))->assertOk()->getContent();

    expect($html)->toContain('Duke')->toContain('MH')->not->toContain('SECRET-REG-2')->not->toContain('985000000000001');
});

test('a dog that has left needs a reason and cannot leave before it arrived', function () {
    $base = ['name' => 'Old Dog', 'sex' => 'male', 'role' => 'retired'];

    $this->post(route('admin.dogs.store'), $base + ['left_on' => '2026-01-01'])->assertSessionHasErrors('left_reason');
    $this->post(route('admin.dogs.store'), $base + ['acquired_on' => '2026-03-01', 'left_on' => '2026-01-01', 'left_reason' => 'sold'])->assertSessionHasErrors('left_on');
    $this->post(route('admin.dogs.store'), $base + ['acquired_on' => '2025-03-01', 'left_on' => '2026-01-01', 'left_reason' => 'sold'])->assertSessionHasNoErrors();
});
