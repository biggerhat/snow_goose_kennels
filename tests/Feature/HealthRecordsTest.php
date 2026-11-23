<?php

use App\Mail\DailyReminders;
use App\Models\Dog;
use App\Models\HealthRecord;
use App\Models\Litter;
use App\Models\User;
use App\Support\Attention;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs(User::factory()->create()));

function bella(): Dog
{
    return Dog::create(['name' => 'Bella', 'sex' => 'female', 'role' => 'dam']);
}

test('a dog can have vaccines and treatments recorded', function () {
    $dog = bella();

    $this->post(route('admin.health-records.dog', $dog), ['type' => 'vaccine', 'name' => 'Rabies', 'given_on' => today()->subMonths(3)->toDateString(), 'next_due_on' => today()->addYear()->toDateString(), 'given_by' => 'Dr. Allen', 'lot_number' => 'L123'])->assertSessionHasNoErrors();

    $r = $dog->healthRecords()->firstOrFail();
    expect($r->name)->toBe('Rabies')->and($r->type->value)->toBe('vaccine')->and($r->lot_number)->toBe('L123');
});

test('health record dates must make sense', function () {
    $dog = bella();
    $base = ['type' => 'vaccine', 'name' => 'Rabies'];

    $this->post(route('admin.health-records.dog', $dog), $base + ['given_on' => today()->addDay()->toDateString()])->assertSessionHasErrors('given_on');
    $this->post(route('admin.health-records.dog', $dog), $base + ['given_on' => '2026-05-01', 'next_due_on' => '2026-04-01'])->assertSessionHasErrors(['next_due_on' => 'The next due date must be after the date it was given.']);
    $this->post(route('admin.health-records.dog', $dog), ['type' => 'vaccine', 'given_on' => '2026-05-01'])->assertSessionHasErrors(['name' => 'Say what it was, like Rabies or Pyrantel.']);
    $this->post(route('admin.health-records.dog', $dog), ['type' => 'magic', 'name' => 'x', 'given_on' => '2026-05-01'])->assertSessionHasErrors('type');
});

test('one entry can be recorded for every puppy in a litter', function () {
    $litter = Litter::create(['status' => 'on_ground', 'born_on' => today()->subWeeks(4)]);
    foreach (range(1, 3) as $i) {
        $litter->puppies()->create(['sex' => 'male', 'status' => 'available']);
    }

    $this->post(route('admin.health-records.litter', $litter), ['type' => 'deworming', 'name' => 'Pyrantel', 'given_on' => today()->toDateString(), 'next_due_on' => today()->addWeeks(2)->toDateString()])->assertSessionHasNoErrors();

    expect(HealthRecord::where('recordable_type', 'puppy')->count())->toBe(3);

    $this->post(route('admin.health-records.litter', $litter), ['type' => 'deworming', 'name' => 'Old', 'given_on' => today()->subWeeks(6)->toDateString()])->assertSessionHasErrors('given_on');
});

test('records can be edited and removed', function () {
    $dog = bella();
    $r = $dog->healthRecords()->create(['type' => 'vaccine', 'name' => 'Rabies', 'given_on' => '2026-01-01']);

    $this->put(route('admin.health-records.update', $r), ['type' => 'vaccine', 'name' => 'Rabies 3-year', 'given_on' => '2026-01-01'])->assertSessionHasNoErrors();
    expect($r->fresh()->name)->toBe('Rabies 3-year');

    $this->delete(route('admin.health-records.destroy', $r))->assertRedirect();
    expect(HealthRecord::count())->toBe(0);
});

test('records go when their dog, puppy or litter is deleted', function () {
    $dog = bella();
    $dog->healthRecords()->create(['type' => 'vaccine', 'name' => 'Rabies', 'given_on' => '2026-01-01']);
    $litter = Litter::create(['status' => 'on_ground', 'born_on' => today()->subWeeks(2)]);
    $pup = $litter->puppies()->create(['sex' => 'male', 'status' => 'available']);
    $pup->healthRecords()->create(['type' => 'deworming', 'name' => 'Pyrantel', 'given_on' => today()->subDay()]);

    // Archiving keeps everything; a permanent delete removes it.
    $dog->forceDelete();
    $litter->forceDelete();

    expect(HealthRecord::count())->toBe(0);
});

test('overdue and upcoming shots are flagged, and a newer entry clears them', function () {
    $dog = bella();
    $old = $dog->healthRecords()->create(['type' => 'vaccine', 'name' => 'Rabies', 'given_on' => today()->subYear(), 'next_due_on' => today()->subDays(5)]);
    $dog->healthRecords()->create(['type' => 'parasite', 'name' => 'Heartworm', 'given_on' => today()->subMonth(), 'next_due_on' => today()->addDays(3)]);
    $dog->healthRecords()->create(['type' => 'vaccine', 'name' => 'DHPP', 'given_on' => today()->subMonth(), 'next_due_on' => today()->addMonths(5)]);

    $items = Attention::items();
    expect($items->pluck('title')->join('|'))->toContain('Rabies due for Bella')->toContain('Heartworm due for Bella')->not->toContain('DHPP')
        ->and($items->firstWhere('kind', 'health')['overdue'])->toBeTrue();

    $dog->healthRecords()->create(['type' => 'vaccine', 'name' => 'rabies', 'given_on' => today(), 'next_due_on' => today()->addYear()]);
    expect(Attention::items()->pluck('title')->join('|'))->not->toContain('Rabies due');
});

test('dogs that left and puppies that went home are not nagged about', function () {
    $dog = bella();
    $dog->healthRecords()->create(['type' => 'vaccine', 'name' => 'Rabies', 'given_on' => today()->subYear(), 'next_due_on' => today()->subDay()]);
    $dog->update(['left_on' => today()->subMonth(), 'left_reason' => 'sold']);

    $litter = Litter::create(['status' => 'on_ground', 'born_on' => today()->subWeeks(9)]);
    $pup = $litter->puppies()->create(['sex' => 'male', 'status' => 'home']);
    $pup->healthRecords()->create(['type' => 'deworming', 'name' => 'Pyrantel', 'given_on' => today()->subWeeks(3), 'next_due_on' => today()->subDay()]);

    expect(Attention::items()->where('kind', 'health'))->toBeEmpty();
});

test('expiring papers and the AKC deadline appear', function () {
    $dog = bella();
    $dog->documents()->create(['category' => 'vet', 'title' => 'Rabies certificate', 'path' => 'x', 'original_name' => 'r.pdf', 'mime' => 'application/pdf', 'size' => 1, 'expires_on' => today()->addDays(10)]);
    Litter::create(['status' => 'on_ground', 'name' => 'Spring', 'born_on' => today()->subMonths(5)->subDays(20)]);

    $titles = Attention::items()->pluck('title')->join('|');
    expect($titles)->toContain('Rabies certificate')->toContain('AKC litter application for Spring');

    $dog->documents()->create(['category' => 'vet', 'title' => 'Rabies 2', 'path' => 'y', 'original_name' => 'r2.pdf', 'mime' => 'application/pdf', 'size' => 1, 'expires_on' => today()->addYear()]);
    expect(Attention::items()->pluck('title')->join('|'))->not->toContain('Rabies certificate');
});

test('the morning email and the dashboard include what needs attention', function () {
    Mail::fake();
    $dog = bella();
    $dog->healthRecords()->create(['type' => 'vaccine', 'name' => 'Rabies', 'given_on' => today()->subYear(), 'next_due_on' => today()->subDay()]);

    $this->artisan('kennel:reminders')->assertSuccessful();
    Mail::assertSent(DailyReminders::class, fn ($m) => $m->attention->where('kind', 'health')->count() === 1);

    $props = $this->get(route('dashboard'))->viewData('page')['props'];
    $health = collect($props['attention'])->where('kind', 'health')->values();
    expect($health)->toHaveCount(1)->and($health[0]['title'])->toContain('Rabies');
});

test('the same treatment for a whole litter shows as one line', function () {
    $litter = Litter::create(['status' => 'on_ground', 'name' => 'Spring', 'born_on' => today()->subWeeks(4)]);
    foreach (range(1, 4) as $i) {
        $litter->puppies()->create(['sex' => 'male', 'status' => 'available']);
    }
    $this->post(route('admin.health-records.litter', $litter), ['type' => 'deworming', 'name' => 'Pyrantel', 'given_on' => today()->subDays(12)->toDateString(), 'next_due_on' => today()->addDay()->toDateString()]);

    $items = Attention::items()->where('kind', 'health');

    expect($items)->toHaveCount(1)->and($items->first()['title'])->toBe('Pyrantel due for 4 puppies in Spring');
});

test('the same treatment for several dogs is one reminder', function () {
    foreach (['Bella', 'Honey', 'Maple'] as $name) {
        Dog::create(['name' => $name, 'sex' => 'female', 'role' => 'dam'])->healthRecords()->create(['type' => 'parasite', 'name' => 'Heartworm preventive', 'given_on' => today()->subDays(25), 'next_due_on' => today()->addDays(2)]);
    }
    foreach (['A', 'B', 'C', 'D', 'E'] as $name) {
        Dog::create(['name' => $name, 'sex' => 'male', 'role' => 'stud'])->healthRecords()->create(['type' => 'vaccine', 'name' => 'Rabies', 'given_on' => today()->subYear(), 'next_due_on' => today()->subDays(3)]);
    }

    $titles = Attention::items()->where('kind', 'health')->pluck('title')->all();

    expect($titles)->toContain('Heartworm preventive due for Bella, Honey and Maple')->toContain('Rabies due for 5 dogs');
});

test('overdue items come first', function () {
    $dog = bella();
    $dog->healthRecords()->create(['type' => 'parasite', 'name' => 'Heartworm', 'given_on' => today()->subDays(20), 'next_due_on' => today()->addDay()]);
    $dog->healthRecords()->create(['type' => 'vaccine', 'name' => 'Rabies', 'given_on' => today()->subYear(), 'next_due_on' => today()->subDays(9)]);

    expect(Attention::items()->first()['title'])->toContain('Rabies')->and(Attention::items()->first()['overdue'])->toBeTrue();
});

test('an item can be snoozed and comes back afterwards', function () {
    $dog = bella();
    $dog->healthRecords()->create(['type' => 'vaccine', 'name' => 'Rabies', 'given_on' => today()->subYear(), 'next_due_on' => today()->subDay()]);
    $item = Attention::items()->firstWhere('kind', 'health');

    $this->post(route('admin.attention.snooze'), ['key' => $item['key'], 'days' => 7])->assertRedirect();
    expect(Attention::items()->where('kind', 'health'))->toBeEmpty();

    $this->travel(8)->days();
    expect(Attention::items()->where('kind', 'health'))->toHaveCount(1);
});

test('snoozing needs a proper key', function () {
    $this->post(route('admin.attention.snooze'), ['key' => 'nope'])->assertSessionHasErrors('key');
});
