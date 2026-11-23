<?php

use App\Models\Dog;
use App\Models\Setting;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows a privacy page', function () {
    $this->get('/privacy')->assertOk()->assertInertia(fn ($p) => $p->component('public/Privacy'));
});

it('shows only public clearances on a public dog page', function () {
    $dog = Dog::create(['name' => 'Bella', 'sex' => 'female', 'role' => 'dam', 'is_public' => true, 'is_ours' => true]);
    $dog->healthTests()->create(['name' => 'OFA Hips', 'result' => 'Good', 'is_public' => true]);
    $dog->healthTests()->create(['name' => 'Secret test', 'result' => 'x', 'is_public' => false]);

    $this->get(route('site.dog', $dog))->assertOk()->assertInertia(fn ($p) => $p
        ->has('dog.health_tests', 1)
        ->where('dog.health_tests.0.name', 'OFA Hips'));
});

it('puts how it works on the contact page and the stories on the home page', function () {
    Testimonial::create(['name' => 'The Millers', 'body' => 'Best dog ever', 'is_public' => true]);
    Testimonial::create(['name' => 'Hidden', 'body' => 'Not for show', 'is_public' => false]);
    Testimonial::create(['name' => 'Waiting', 'body' => 'Sent in by a visitor', 'is_public' => false, 'needs_review' => true]);
    Setting::put('deposit_policy', 'Deposits are $300.');

    $this->get('/contact')->assertOk()->assertInertia(fn ($p) => $p->component('public/Contact')->has('sections', 1)->missing('testimonials'));
    $this->get('/')->assertOk()->assertInertia(fn ($p) => $p->has('quotes', 1)->where('quotes.0.name', 'The Millers'));
});

it('sends the old page addresses to where the content lives now', function () {
    $this->get('/how-it-works')->assertRedirect('/contact#how-it-works')->assertStatus(301);
    $this->get('/happy-families')->assertRedirect('/#happy-families');
    $this->get('/health-testing')->assertRedirect('/our-dogs');
});

it('lists the privacy page but not the retired pages in the sitemap', function () {
    $this->get('/sitemap.xml')->assertOk()->assertSee('/privacy', false)
        ->assertDontSee('/health-testing', false)->assertDontSee('/how-it-works', false)->assertDontSee('/happy-families', false);
});
