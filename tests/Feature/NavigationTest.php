<?php

use App\Models\Contact;
use App\Models\Dog;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\WaitlistEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('the public menu shows Happy families once there is a public story', function () {
    expect($this->get('/')->viewData('page')['props']['site']['hasFamilies'])->toBeFalse();

    Testimonial::create(['name' => 'The Millers', 'body' => 'Great', 'is_public' => true]);

    expect($this->get('/')->viewData('page')['props']['site']['hasFamilies'])->toBeTrue();
});

test('a hidden story does not add the menu item', function () {
    Testimonial::create(['name' => 'Private', 'body' => 'x', 'is_public' => false]);

    expect($this->get('/')->viewData('page')['props']['site']['hasFamilies'])->toBeFalse();
});

test('the owner menu counts new applications and things needing attention', function () {
    Cache::flush();
    $this->actingAs(User::factory()->create());
    $contact = Contact::create(['name' => 'Jane', 'type' => 'buyer']);
    WaitlistEntry::create(['contact_id' => $contact->id, 'status' => 'new']);
    WaitlistEntry::create(['contact_id' => $contact->id, 'status' => 'waiting']);

    $nav = $this->get(route('dashboard'))->viewData('page')['props']['nav'];

    expect($nav['applications'])->toBe(1)->and($nav['attention'])->toBeInt();
});

test('visitors never get the owner menu counts', function () {
    expect($this->get('/')->viewData('page')['props']['nav'])->toBeNull();
});

test('every owner page still opens', function () {
    $this->actingAs(User::factory()->create());
    $dog = Dog::create(['name' => 'Bella', 'sex' => 'female', 'role' => 'dam']);

    foreach (['dashboard', 'admin.dogs.index', 'admin.litters.index', 'admin.waitlist.index', 'admin.contacts.index', 'admin.planner', 'admin.photos.index', 'admin.records', 'admin.testimonials.index', 'admin.site.edit', 'admin.help'] as $name) {
        $this->get(route($name))->assertOk();
    }

    $this->get(route('admin.dogs.edit', $dog))->assertOk();
});
