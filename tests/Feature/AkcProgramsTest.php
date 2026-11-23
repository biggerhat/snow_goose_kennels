<?php

use App\Models\Dog;
use App\Models\Litter;
use App\Models\Setting;
use App\Models\User;
use App\Support\AkcPrograms;
use App\Support\LitterHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs(User::factory()->create()));

function item(array $report, string $program, string $key): array
{
    return collect($report[$program]['items'])->firstWhere('key', $key);
}

it('checks what it can see and what she confirms for Bred with H.E.A.R.T.', function () {
    $dam = Dog::create(['name' => 'Bella', 'sex' => 'female', 'role' => 'dam', 'is_ours' => true]);

    $r = AkcPrograms::report();
    expect(item($r, 'heart', 'litter')['status'])->toBe('todo')
        ->and(item($r, 'heart', 'health')['status'])->toBe('todo')
        ->and(item($r, 'heart', 'standing')['status'])->toBe('todo');

    Litter::create(['dam_id' => $dam->id, 'status' => 'all_home', 'born_on' => today()->subYear()->toDateString(), 'akc_litter_number' => 'NP1']);
    $dam->healthTests()->create(['name' => 'OFA Hips', 'result' => 'Good']);
    Setting::put('akc_good_standing', '1');
    Setting::put('akc_education_done_on', today()->subMonths(2)->toDateString());

    $r = AkcPrograms::report();
    expect(item($r, 'heart', 'litter')['status'])->toBe('ok')
        ->and(item($r, 'heart', 'health')['status'])->toBe('ok')
        ->and(item($r, 'heart', 'standing')['status'])->toBe('ok')
        ->and(item($r, 'heart', 'education')['status'])->toBe('ok');
});

it('treats an old course date as not done', function () {
    Setting::put('akc_education_done_on', today()->subYears(2)->toDateString());

    expect(item(AkcPrograms::report(), 'heart', 'education')['status'])->toBe('todo');
});

it('counts puppies with an AKC number for Breeder of Merit', function () {
    $litter = Litter::create(['status' => 'all_home', 'born_on' => today()->subYear()->toDateString()]);
    $litter->puppies()->create(['sex' => 'female', 'status' => 'home', 'akc_number' => 'NP1']);
    $litter->puppies()->create(['sex' => 'male', 'status' => 'home']);
    Setting::put('akc_years_in_events', '6');

    $r = AkcPrograms::report();
    expect(item($r, 'merit', 'register'))->toMatchArray(['status' => 'todo', 'detail' => '1 of 2 puppies that went home have an AKC number recorded.'])
        ->and(item($r, 'merit', 'years')['status'])->toBe('ok');
});

it('saves what only she knows and rejects a future course date', function () {
    $this->put(route('admin.akc-programs.update'), ['years' => 7, 'club' => true, 'standing' => true, 'education' => today()->toDateString()])->assertRedirect();
    expect(Setting::get('akc_years_in_events'))->toBe('7')->and(Setting::get('akc_club_member'))->toBe('1');

    $this->put(route('admin.akc-programs.update'), ['years' => 'many', 'education' => today()->addDay()->toDateString()])->assertSessionHasErrors(['years', 'education']);
});

it('puts the programs on the Records page', function () {
    $this->get(route('admin.records'))->assertInertia(fn ($p) => $p->has('programs.heart.items', 4)->has('programs.merit.items', 5));
});

it('describes a dams litter history without making up a rule', function () {
    $dam = Dog::create(['name' => 'Bella', 'sex' => 'female', 'role' => 'dam']);
    expect(LitterHistory::forDam($dam)['text'])->toContain('no earlier litters');

    Litter::create(['dam_id' => $dam->id, 'status' => 'all_home', 'born_on' => today()->subMonths(13)->toDateString()]);
    Litter::create(['dam_id' => $dam->id, 'status' => 'planned']);

    $h = LitterHistory::forDam($dam);
    expect($h['count'])->toBe(1)->and($h['months_ago'])->toBe(13)->and($h['text'])->toContain('Ask your vet')->and($h['text'])->not->toContain('AKC');
    expect(LitterHistory::forDam(null))->toBeNull();
});

it('shows the dams history on the litter page and the planner', function () {
    $dam = Dog::create(['name' => 'Bella', 'sex' => 'female', 'role' => 'dam']);
    $sire = Dog::create(['name' => 'Duke', 'sex' => 'male', 'role' => 'stud']);
    $litter = Litter::create(['dam_id' => $dam->id, 'sire_id' => $sire->id, 'status' => 'planned']);

    $this->get(route('admin.litters.edit', $litter))->assertInertia(fn ($p) => $p->where('damHistory.count', 0));
    $this->get(route('admin.planner', ['sire' => $sire->id, 'dam' => $dam->id]))->assertInertia(fn ($p) => $p->has('result.damHistory.text'));
});
