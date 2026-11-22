<?php

use App\Models\Contact;
use App\Models\Dog;
use App\Models\Litter;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs(User::factory()->create()));

test('a weigh-in records a weight per puppy and replaces one on the same day', function () {
    $litter = Litter::create(['status' => 'on_ground']);
    $a = $litter->puppies()->create(['sex' => 'male', 'status' => 'available']);
    $b = $litter->puppies()->create(['sex' => 'female', 'status' => 'available']);

    $this->post(route('admin.weigh-ins.store', $litter), ['weighed_on' => '2026-11-01', 'weights' => [$a->id => 900, $b->id => null]])->assertRedirect();
    $this->post(route('admin.weigh-ins.store', $litter), ['weighed_on' => '2026-11-01', 'weights' => [$a->id => 950]])->assertRedirect();

    expect($a->weights()->count())->toBe(1)->and($a->weights()->first()->weight_g)->toBe(950)->and($b->weights()->count())->toBe(0);
});

test('weigh-ins ignore puppies from other litters and future dates', function () {
    $mine = Litter::create(['status' => 'on_ground']);
    $other = Litter::create(['status' => 'on_ground']);
    $stranger = $other->puppies()->create(['sex' => 'male', 'status' => 'available']);

    $this->post(route('admin.weigh-ins.store', $mine), ['weighed_on' => '2026-11-01', 'weights' => [$stranger->id => 800]]);
    expect($stranger->weights()->count())->toBe(0);

    $this->post(route('admin.weigh-ins.store', $mine), ['weighed_on' => now()->addWeek()->toDateString(), 'weights' => [1 => 800]])->assertSessionHasErrors('weighed_on');
});

test('the litter becomes "all gone home" when its last puppy goes home', function () {
    $litter = Litter::create(['status' => 'on_ground']);
    $a = $litter->puppies()->create(['sex' => 'male', 'status' => 'home']);
    $b = $litter->puppies()->create(['sex' => 'female', 'status' => 'reserved']);

    $this->put(route('admin.puppies.update', $b), ['sex' => 'female', 'status' => 'home'])->assertRedirect();

    expect($litter->fresh()->status->value)->toBe('all_home');
});

test('a litter with puppies still waiting stays on the ground', function () {
    $litter = Litter::create(['status' => 'on_ground']);
    $a = $litter->puppies()->create(['sex' => 'male', 'status' => 'available']);
    $litter->puppies()->create(['sex' => 'female', 'status' => 'available']);

    $this->put(route('admin.puppies.update', $a), ['sex' => 'male', 'status' => 'home']);

    expect($litter->fresh()->status->value)->toBe('on_ground');
});

test('csv export contains the data, is protected from formula injection, and requires login', function () {
    Contact::create(['name' => '=HYPERLINK("http://evil")', 'type' => 'buyer', 'phone' => '555']);

    $csv = $this->get(route('admin.export', 'contacts'))->assertOk()->streamedContent();
    expect($csv)->toContain("'=HYPERLINK")->not->toContain(',=HYPERLINK');

    $this->get(route('admin.export', 'nonsense'))->assertNotFound();

    auth()->logout();
    $this->get(route('admin.export', 'contacts'))->assertRedirect(route('login'));
});

test('testimonials show on the public site only when public', function () {
    Testimonial::create(['name' => 'The Millers', 'body' => 'PUBLIC-STORY', 'is_public' => true]);
    Testimonial::create(['name' => 'Hidden Family', 'body' => 'HIDDEN-STORY', 'is_public' => false]);

    $html = $this->get(route('site.families'))->getContent();
    expect($html)->toContain('PUBLIC-STORY')->not->toContain('HIDDEN-STORY');
    expect($this->get(route('home'))->getContent())->not->toContain('HIDDEN-STORY');
});

test('heat cycles are recorded for a dog', function () {
    $dog = Dog::create(['name' => 'Bella', 'sex' => 'female', 'role' => 'dam']);

    $this->post(route('admin.heat-cycles.store', $dog), ['started_on' => '2026-03-01', 'bred_on' => '2026-03-12'])->assertRedirect();
    $this->post(route('admin.heat-cycles.store', $dog), ['started_on' => '2026-03-10', 'ended_on' => '2026-03-01'])->assertSessionHasErrors('ended_on');

    expect($dog->heatCycles()->count())->toBe(1);
    $this->get(route('admin.dogs.edit', $dog))->assertOk();
});

test('dashboard lists litters due or going home soon', function () {
    Litter::create(['status' => 'expected', 'name' => 'Soon Litter', 'due_on' => today()->addDays(10)]);

    $this->get(route('dashboard'))->assertOk()->assertSee('Soon Litter')->assertSee('Due');
});
