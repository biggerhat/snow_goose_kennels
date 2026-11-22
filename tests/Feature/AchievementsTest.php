<?php

use App\Models\Dog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs(User::factory()->create()));

test('titles are listed and build the dog’s Titles box in date order', function () {
    $dog = Dog::create(['name' => 'Duke', 'sex' => 'male', 'role' => 'stud']);

    $this->post(route('admin.achievements.store', $dog), ['title' => 'MH', 'full_name' => 'Master Hunter', 'kind' => 'hunt_test', 'earned_on' => '2025-06-01', 'event' => 'Spring Test'])->assertSessionHasNoErrors();
    $this->post(route('admin.achievements.store', $dog), ['title' => 'SH', 'kind' => 'hunt_test', 'earned_on' => '2024-05-01'])->assertSessionHasNoErrors();

    expect($dog->fresh()->titles)->toBe('SH, MH');
});

test('removing or editing a title updates the Titles box', function () {
    $dog = Dog::create(['name' => 'Duke', 'sex' => 'male', 'role' => 'stud']);
    $this->post(route('admin.achievements.store', $dog), ['title' => 'SH', 'kind' => 'hunt_test']);
    $a = $dog->achievements()->first();

    $this->put(route('admin.achievements.update', $a), ['title' => 'JH', 'kind' => 'hunt_test'])->assertSessionHasNoErrors();
    expect($dog->fresh()->titles)->toBe('JH');

    $this->delete(route('admin.achievements.destroy', $a));
    expect($dog->fresh()->titles)->toBeNull();
});

test('the Titles box is not overwritten by hand once titles are listed', function () {
    $dog = Dog::create(['name' => 'Duke', 'sex' => 'male', 'role' => 'stud']);
    $this->post(route('admin.achievements.store', $dog), ['title' => 'MH', 'kind' => 'hunt_test']);

    $this->put(route('admin.dogs.update', $dog), ['name' => 'Duke', 'sex' => 'male', 'role' => 'stud', 'titles' => 'CH GCH'])->assertSessionHasNoErrors();

    expect($dog->fresh()->titles)->toBe('MH');
});

test('a title needs letters and a real kind', function () {
    $dog = Dog::create(['name' => 'Duke', 'sex' => 'male', 'role' => 'stud']);

    $this->post(route('admin.achievements.store', $dog), ['kind' => 'hunt_test'])->assertSessionHasErrors(['title' => 'Enter the letters, like MH or SH.']);
    $this->post(route('admin.achievements.store', $dog), ['title' => 'MH', 'kind' => 'bake_sale'])->assertSessionHasErrors('kind');
    $this->post(route('admin.achievements.store', $dog), ['title' => 'MH', 'kind' => 'show', 'earned_on' => now()->addDay()->toDateString()])->assertSessionHasErrors('earned_on');
});

test('titles show on the public pedigree', function () {
    $dog = Dog::create(['name' => 'Duke', 'sex' => 'male', 'role' => 'stud', 'is_public' => true]);
    $this->post(route('admin.achievements.store', $dog), ['title' => 'MH', 'kind' => 'hunt_test']);

    expect($this->get(route('site.dog', $dog))->getContent())->toContain('MH');
});
