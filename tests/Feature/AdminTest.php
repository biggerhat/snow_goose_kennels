<?php

use App\Models\Contact;
use App\Models\Dog;
use App\Models\Litter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin pages require login', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with(['dashboard', 'admin.dogs.index', 'admin.litters.index', 'admin.contacts.index', 'admin.waitlist.index', 'admin.photos.index']);

test('owner can manage dogs, litters, contacts and the waitlist', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('admin.dogs.store'), ['name' => 'Bella', 'sex' => 'female', 'role' => 'dam', 'is_public' => true])->assertRedirect();
    $dog = Dog::firstWhere('slug', 'bella');
    expect($dog)->not->toBeNull();

    $this->post(route('admin.litters.store'), ['dam_id' => $dog->id, 'status' => 'expected', 'due_on' => '2027-03-01', 'price' => 1500])->assertRedirect();
    $litter = Litter::first();
    expect($litter->title)->toBe('Bella')->and($litter->due_on->toDateString())->toBe('2027-03-01');

    $this->post(route('admin.puppies.store', $litter), ['sex' => 'male', 'status' => 'available'])->assertRedirect();
    expect($litter->puppies()->count())->toBe(1);

    $this->post(route('admin.waitlist.store'), ['new_name' => 'Jane', 'new_phone' => '555', 'litter_id' => $litter->id, 'status' => 'waiting'])->assertRedirect(route('admin.waitlist.index'));
    expect(Contact::firstWhere('name', 'Jane'))->not->toBeNull();

    foreach (['dashboard', 'admin.dogs.index', 'admin.litters.index', 'admin.contacts.index', 'admin.waitlist.index', 'admin.photos.index'] as $r) {
        $this->get(route($r))->assertOk();
    }
    $this->get(route('admin.litters.edit', $litter))->assertOk();
    $this->get(route('admin.dogs.edit', $dog))->assertOk();
});

test('public site never exposes private data', function () {
    $dog = Dog::create(['name' => 'Secret', 'sex' => 'male', 'role' => 'stud', 'is_public' => true, 'microchip' => 'MICROCHIP123', 'private_notes' => 'PRIVATE-NOTE']);
    Dog::create(['name' => 'Hidden', 'sex' => 'male', 'role' => 'stud', 'is_public' => false]);
    $contact = Contact::create(['name' => 'Buyer Person', 'type' => 'buyer', 'phone' => '555-9999']);
    $litter = Litter::create(['sire_id' => $dog->id, 'status' => 'on_ground', 'private_notes' => 'LITTER-PRIVATE', 'born_on' => '2026-10-01']);
    $litter->puppies()->create(['sex' => 'female', 'status' => 'reserved', 'contact_id' => $contact->id, 'notes' => 'PUPPY-PRIVATE']);

    foreach (['home', 'site.litters', 'site.dogs', 'site.gallery', 'site.contact'] as $r) {
        $html = $this->get(route($r))->assertOk()->getContent();
        foreach (['MICROCHIP123', 'PRIVATE-NOTE', 'LITTER-PRIVATE', 'PUPPY-PRIVATE', 'Buyer Person', '555-9999'] as $secret) {
            expect($html)->not->toContain($secret);
        }
    }

    $this->get(route('site.dog', $dog))->assertOk();
    $this->get(route('site.dog', Dog::firstWhere('name', 'Hidden')))->assertNotFound();
});

test('registration is disabled', function () {
    $this->get('/register')->assertNotFound();
});
