<?php

use App\Models\Litter;
use App\Models\LitterTask;
use App\Models\User;
use App\Support\Attention;
use App\Support\PuppySchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs(User::factory()->create()));

function bornLitter(array $over = []): Litter
{
    return Litter::create($over + ['status' => 'on_ground', 'born_on' => today()->subWeeks(2)->toDateString(), 'name' => 'Test']);
}

it('counts the schedule from the birth date', function () {
    $litter = bornLitter(['born_on' => '2026-01-01']);
    $steps = collect(PuppySchedule::for($litter));

    expect($steps->firstWhere('title', 'Deworm the puppies (2 weeks)')['due_on']->toDateString())->toBe('2026-01-15')
        ->and($steps->firstWhere('kind', 'home')['due_on']->toDateString())->toBe('2026-02-26')
        ->and($steps->pluck('due_on')->map->timestamp->all())->toBe($steps->pluck('due_on')->map->timestamp->sort()->values()->all());
});

it('uses the go-home date when there is one', function () {
    $litter = bornLitter(['born_on' => '2026-01-01', 'go_home_on' => '2026-03-01']);

    expect(collect(PuppySchedule::for($litter))->firstWhere('kind', 'home')['due_on']->toDateString())->toBe('2026-03-01');
});

it('makes no schedule without a birth date', function () {
    expect(PuppySchedule::for(Litter::create(['status' => 'planned', 'name' => 'Later'])))->toBe([]);
});

it('does not add the same steps twice', function () {
    $litter = bornLitter();

    $first = PuppySchedule::create($litter);
    $second = PuppySchedule::create($litter);

    expect($first)->toBeGreaterThan(8)->and($second)->toBe(0);
});

it('creates the schedule automatically when a litter with a birth date is first saved', function () {
    $litter = Litter::create(['status' => 'expected', 'name' => 'Soon']);

    $this->put(route('admin.litters.update', $litter), ['status' => 'on_ground', 'born_on' => today()->subWeek()->toDateString(), 'is_public' => true])->assertRedirect();

    expect($litter->tasks()->count())->toBeGreaterThan(8);
});

it('builds the schedule on request and refuses without a birth date', function () {
    $none = Litter::create(['status' => 'planned', 'name' => 'Later']);
    $this->post(route('admin.tasks.generate', $none))->assertRedirect();
    expect($none->tasks()->count())->toBe(0);

    $born = bornLitter();
    $this->post(route('admin.tasks.generate', $born))->assertRedirect();
    expect($born->tasks()->count())->toBeGreaterThan(8);
});

it('ticks a step off, moves it and removes it', function () {
    $task = bornLitter()->tasks()->create(['title' => 'Vet', 'due_on' => today()->toDateString()]);

    $this->put(route('admin.tasks.update', $task), ['done' => true]);
    expect($task->fresh()->done_on?->toDateString())->toBe(today()->toDateString());

    $this->put(route('admin.tasks.update', $task), ['done' => false, 'due_on' => '2030-01-01']);
    expect($task->fresh()->done_on)->toBeNull()->and($task->fresh()->due_on->toDateString())->toBe('2030-01-01');

    $this->delete(route('admin.tasks.destroy', $task));
    expect(LitterTask::count())->toBe(0);
});

it('lets her add her own step and explains a missing title', function () {
    $litter = bornLitter();

    $this->post(route('admin.tasks.store', $litter), ['title' => '', 'due_on' => ''])->assertSessionHasErrors(['title', 'due_on']);
    $this->post(route('admin.tasks.store', $litter), ['title' => 'Nail trim', 'due_on' => today()->toDateString()]);

    expect($litter->tasks()->where('title', 'Nail trim')->exists())->toBeTrue();
});

it('shows late and due steps on the dashboard, but not finished ones or archived litters', function () {
    $litter = bornLitter(['name' => 'Honey']);
    $litter->tasks()->create(['title' => 'Late step', 'due_on' => today()->subDay()->toDateString()]);
    $litter->tasks()->create(['title' => 'Done step', 'due_on' => today()->subDay()->toDateString(), 'done_on' => today()->toDateString()]);
    $litter->tasks()->create(['title' => 'Far step', 'due_on' => today()->addMonths(2)->toDateString()]);

    $titles = Attention::items()->pluck('title');
    expect($titles->contains(fn ($t) => str_contains($t, 'Late step')))->toBeTrue()
        ->and($titles->contains(fn ($t) => str_contains($t, 'Done step')))->toBeFalse()
        ->and($titles->contains(fn ($t) => str_contains($t, 'Far step')))->toBeFalse();

    $litter->delete();
    expect(Attention::items()->contains(fn ($i) => $i['kind'] === 'schedule'))->toBeFalse();
});
