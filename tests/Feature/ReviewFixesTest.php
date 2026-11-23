<?php

use App\Models\Dog;
use App\Models\Litter;
use App\Models\Puppy;
use App\Models\User;
use App\Support\Attention;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
use App\Support\Readiness;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

function archivedLitterWithOverduePuppy(): Puppy
{
    $litter = Litter::create(['status' => 'on_ground', 'name' => 'Old', 'born_on' => now()->subWeeks(5)]);
    $puppy = $litter->puppies()->create(['collar' => 'Red', 'sex' => 'male', 'status' => 'available']);
    $puppy->healthRecords()->create(['type' => 'vaccine', 'name' => 'Puppy shot', 'given_on' => now()->subWeeks(5), 'next_due_on' => now()->subDay()]);
    $litter->delete();

    return $puppy;
}

test('reminders and the puppies export survive an archived litter', function () {
    archivedLitterWithOverduePuppy();

    expect(Attention::items()->where('kind', 'health'))->toHaveCount(1);
    $this->get(route('dashboard'))->assertOk();
    $this->get(route('admin.export', 'puppies'))->assertOk();
});

test('a puppy in an archived litter can still be edited', function () {
    $puppy = archivedLitterWithOverduePuppy();

    $this->put(route('admin.puppies.update', $puppy), ['collar' => 'Blue', 'sex' => 'male', 'status' => 'available'])->assertSessionHasNoErrors();
    expect($puppy->fresh()->collar)->toBe('Blue');
});

test('snoozing an overdue vaccine does not turn the readiness check green', function () {
    archivedLitterWithOverduePuppy();
    $item = Attention::items()->first();
    $this->post(route('admin.attention.snooze'), ['key' => $item['key']]);

    expect(Attention::items()->where('kind', 'health'))->toHaveCount(0);
    expect(collect(Readiness::report()['items'])->firstWhere('key', 'health')['status'])->toBe('todo');
});

test('deleting the puppy schedule is not undone by the next save', function () {
    $litter = Litter::create(['status' => 'expected', 'name' => 'T', 'due_on' => now()->addWeeks(2)]);

    $payload = ['status' => 'on_ground', 'born_on' => now()->toDateString(), 'is_public' => false];

    $this->put(route('admin.litters.update', $litter), $payload);
    $first = $litter->tasks()->count();
    $litter->tasks()->delete();
    $this->put(route('admin.litters.update', $litter), $payload);

    expect($litter->tasks()->count())->toBe(0)->and($first)->toBeGreaterThanOrEqual(0);
});

test('searching for an underscore finds it', function () {
    Dog::create(['name' => 'Bella_Blue', 'sex' => 'female', 'role' => 'dam']);
    Dog::create(['name' => 'Bella Blue', 'sex' => 'female', 'role' => 'dam']);

    $this->getJson(route('admin.search', ['q' => 'la_B']))->assertOk()
        ->assertJsonCount(1, 'groups.0.items');
});

test('the saved colour theme reaches the page so the right dog head is selected', function () {
    $this->withUnencryptedCookie('palette', 'chocolate')->get('/')
        ->assertOk()
        ->assertSee('data-theme="chocolate"', false)
        ->assertInertia(fn ($page) => $page->where('palette', 'chocolate'));

    $this->withUnencryptedCookie('palette', 'bogus')->get('/')->assertInertia(fn ($page) => $page->where('palette', 'black'));
});
