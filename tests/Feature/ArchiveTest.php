<?php

use App\Models\Dog;
use App\Models\Litter;
use App\Models\User;
use App\Support\Pedigree;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs(User::factory()->create()));

function archivedDog(array $over = []): Dog
{
    return Dog::create($over + ['name' => 'Old Dog', 'sex' => 'male', 'role' => 'stud', 'is_public' => true]);
}

test('deleting a dog archives it and keeps its records', function () {
    $dog = archivedDog();
    $dog->healthRecords()->create(['type' => 'vaccine', 'name' => 'Rabies', 'given_on' => '2026-01-01']);

    $this->delete(route('admin.dogs.destroy', $dog))->assertRedirect(route('admin.dogs.index'));

    expect(Dog::count())->toBe(0)->and(Dog::onlyTrashed()->count())->toBe(1)->and($dog->healthRecords()->count())->toBe(1);
    $props = $this->get(route('admin.dogs.index'))->viewData('page')['props'];
    expect($props['dogs'])->toHaveCount(0)->and($props['archived'])->toHaveCount(1);
    expect(collect($this->get(route('site.dogs'))->viewData('page')['props']['dogs'])->pluck('name'))->not->toContain('Old Dog');
});

test('an archived dog can be brought back', function () {
    $dog = archivedDog();
    $dog->delete();

    $this->post(route('admin.dogs.restore', $dog))->assertRedirect();

    expect(Dog::count())->toBe(1);
});

test('an archived dog still appears in pedigrees and record books', function () {
    $sire = archivedDog(['name' => 'Granddad']);
    $pup = Dog::create(['name' => 'Hazel', 'sex' => 'female', 'role' => 'dam', 'sire_id' => $sire->id]);
    $sire->delete();

    expect(Pedigree::build($pup->fresh(), 2)['sire']['name'])->toBe('Granddad');
    expect($this->get(route('admin.records.dog', $sire))->assertOk()->getContent())->toContain('Granddad');
    expect($this->get(route('admin.records.all'))->getContent())->toContain('Dog record: Granddad');
    expect($this->get(route('admin.export', 'dogs'))->streamedContent())->toContain('Granddad');
});

test('a dog cannot be erased for good during the 5 year record-keeping period', function () {
    $dog = archivedDog(['left_on' => today()->subYear(), 'left_reason' => 'sold']);
    $dog->delete();

    $this->delete(route('admin.dogs.purge', $dog))->assertRedirect();
    expect(Dog::withTrashed()->count())->toBe(1);

    $this->travel(6)->years();
    $this->delete(route('admin.dogs.purge', $dog))->assertRedirect(route('admin.dogs.index'));
    expect(Dog::withTrashed()->count())->toBe(0);
});

test('a dog added by mistake can be erased straight away', function () {
    $dog = archivedDog(['name' => 'Typo']);
    $dog->delete();

    $this->delete(route('admin.dogs.purge', $dog))->assertRedirect(route('admin.dogs.index'));

    expect(Dog::withTrashed()->count())->toBe(0);
});

test('a dog that has been bred is protected even if it is new', function () {
    $sire = archivedDog(['name' => 'Sire']);
    Litter::create(['status' => 'expected', 'sire_id' => $sire->id]);
    $sire->delete();

    $this->delete(route('admin.dogs.purge', $sire));

    expect(Dog::withTrashed()->count())->toBe(1);
});

test('a name can be reused after the old dog is archived', function () {
    $old = archivedDog(['name' => 'Bella']);
    $old->delete();

    $this->post(route('admin.dogs.store'), ['name' => 'Bella', 'sex' => 'female', 'role' => 'dam'])->assertSessionHasNoErrors();

    expect(Dog::count())->toBe(1)->and(Dog::first()->slug)->not->toBe($old->slug);
});

test('litters archive and restore, and a born litter is protected for 5 years', function () {
    $born = Litter::create(['status' => 'on_ground', 'name' => 'Spring', 'born_on' => today()->subMonths(2)]);
    $planned = Litter::create(['status' => 'planned', 'name' => 'Maybe']);

    $this->delete(route('admin.litters.destroy', $born));
    $this->delete(route('admin.litters.destroy', $planned));
    expect(Litter::count())->toBe(0)->and(Litter::onlyTrashed()->count())->toBe(2);

    $this->delete(route('admin.litters.purge', $born));
    $this->delete(route('admin.litters.purge', $planned));
    expect(Litter::onlyTrashed()->pluck('name')->all())->toBe(['Spring']);

    $this->post(route('admin.litters.restore', $born))->assertRedirect();
    expect(Litter::count())->toBe(1);
});

test('an archived litter is not shown to visitors', function () {
    $litter = Litter::create(['status' => 'expected', 'name' => 'HiddenLitter', 'is_public' => true]);
    $litter->delete();

    expect(collect($this->get(route('site.litters'))->viewData('page')['props']['upcoming'])->pluck('title'))->not->toContain('HiddenLitter');
});
