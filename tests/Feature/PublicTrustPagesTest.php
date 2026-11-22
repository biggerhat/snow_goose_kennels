<?php

use App\Models\Dog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows a privacy page', function () {
    $this->get('/privacy')->assertOk()->assertInertia(fn ($p) => $p->component('public/Privacy'));
});

it('lists only public clearances of our public breeding dogs on the health testing page', function () {
    $dog = Dog::create(['name' => 'Bella', 'sex' => 'female', 'role' => 'dam', 'is_public' => true, 'is_ours' => true]);
    $other = Dog::create(['name' => 'Outsider', 'sex' => 'female', 'role' => 'dam', 'is_public' => true, 'is_ours' => false]);
    $dog->healthTests()->create(['name' => 'OFA Hips', 'result' => 'Good', 'is_public' => true]);
    $dog->healthTests()->create(['name' => 'Secret test', 'result' => 'x', 'is_public' => false]);
    $other->healthTests()->create(['name' => 'Outside hips', 'result' => 'Fair', 'is_public' => true]);

    $this->get('/health-testing')->assertOk()->assertInertia(fn ($p) => $p
        ->component('public/HealthTesting')
        ->has('dogs', 1)
        ->where('dogs.0.health_tests.0.name', 'OFA Hips')
        ->has('dogs.0.health_tests', 1));
});

it('includes the health testing page in the sitemap', function () {
    $this->get('/sitemap.xml')->assertOk()->assertSee('/health-testing', false)->assertSee('/privacy', false);
});
