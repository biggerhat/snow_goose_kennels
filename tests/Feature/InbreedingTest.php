<?php

use App\Models\Dog;
use App\Models\Litter;
use App\Models\User;
use App\Support\Inbreeding;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function dg(string $name, string $sex, array $parents = []): Dog
{
    return Dog::create($parents + ['name' => $name, 'sex' => $sex, 'role' => $sex === 'male' ? 'stud' : 'dam']);
}

test('unrelated dogs have no inbreeding', function () {
    $r = Inbreeding::forMating(dg('A', 'male'), dg('B', 'female'));

    expect($r['percent'])->toBe(0.0)->and($r['common'])->toBe([]);
});

test('full siblings come to 25 percent', function () {
    $sire = dg('Sire', 'male');
    $dam = dg('Dam', 'female');
    $brother = dg('Brother', 'male', ['sire_id' => $sire->id, 'dam_id' => $dam->id]);
    $sister = dg('Sister', 'female', ['sire_id' => $sire->id, 'dam_id' => $dam->id]);

    $r = Inbreeding::forMating($brother, $sister);

    expect($r['percent'])->toBe(25.0)->and($r['common'])->toContain('Sire')->toContain('Dam');
});

test('half siblings come to 12.5 percent', function () {
    $sire = dg('Sire', 'male');
    $dam1 = dg('Dam1', 'female');
    $dam2 = dg('Dam2', 'female');
    $a = dg('A', 'male', ['sire_id' => $sire->id, 'dam_id' => $dam1->id]);
    $b = dg('B', 'female', ['sire_id' => $sire->id, 'dam_id' => $dam2->id]);

    expect(Inbreeding::forMating($a, $b)['percent'])->toBe(12.5);
});

test('first cousins come to 6.25 percent', function () {
    $gsire = dg('GSire', 'male');
    $gdam = dg('GDam', 'female');
    $parentA = dg('ParentA', 'male', ['sire_id' => $gsire->id, 'dam_id' => $gdam->id]);
    $parentB = dg('ParentB', 'female', ['sire_id' => $gsire->id, 'dam_id' => $gdam->id]);
    $mate1 = dg('Mate1', 'female');
    $mate2 = dg('Mate2', 'male');
    $cousinM = dg('CousinM', 'male', ['sire_id' => $parentA->id, 'dam_id' => $mate1->id]);
    $cousinF = dg('CousinF', 'female', ['sire_id' => $mate2->id, 'dam_id' => $parentB->id]);

    expect(Inbreeding::forMating($cousinM, $cousinF)['percent'])->toBe(6.25);
});

test('parent and child come to 25 percent', function () {
    $sire = dg('Sire', 'male');
    $dam = dg('Dam', 'female');
    $daughter = dg('Daughter', 'female', ['sire_id' => $sire->id, 'dam_id' => $dam->id]);

    expect(Inbreeding::forMating($sire, $daughter)['percent'])->toBe(25.0);
});

test('an archived ancestor still counts', function () {
    $sire = dg('Sire', 'male');
    $dam = dg('Dam', 'female');
    $a = dg('A', 'male', ['sire_id' => $sire->id, 'dam_id' => $dam->id]);
    $b = dg('B', 'female', ['sire_id' => $sire->id, 'dam_id' => $dam->id]);
    $sire->delete();

    expect(Inbreeding::forMating($a, $b)['percent'])->toBe(25.0);
});

test('the pairing planner shows relatedness, clearances and AKC points', function () {
    $this->actingAs(User::factory()->create());
    $sire = dg('Duke', 'male', ['registry_number' => 'SR1', 'birth_date' => today()->subYears(4)]);
    $dam = dg('Bella', 'female', ['registry_number' => 'SR2', 'birth_date' => today()->subYears(3)]);
    $dam->healthTests()->create(['name' => 'OFA Hips', 'result' => 'Good']);

    $props = $this->get(route('admin.planner', ['sire' => $sire->id, 'dam' => $dam->id]))->assertOk()->viewData('page')['props'];

    expect($props['result']['inbreeding']['percent'])->toBe(0.0)
        ->and(collect($props['result']['parents'][1]['clearances'])->firstWhere('label', 'Hips')['done'])->toBeTrue()
        ->and(collect($props['result']['checks'])->pluck('key')->all())->toContain('parents')->toContain('dam_age');

    expect($this->get(route('admin.planner'))->viewData('page')['props']['result'])->toBeNull();
    expect($this->get(route('admin.planner', ['sire' => $dam->id, 'dam' => $sire->id]))->viewData('page')['props']['result'])->toBeNull();
});

test('a planned litter can start with both parents filled in', function () {
    $this->actingAs(User::factory()->create());
    $sire = dg('Duke', 'male');
    $dam = dg('Bella', 'female');

    $props = $this->get(route('admin.litters.create', ['sire' => $sire->id, 'dam' => $dam->id]))->assertOk()->viewData('page')['props'];

    expect($props['preset'])->toBe(['sire' => $sire->id, 'dam' => $dam->id]);
});

test('a litter page shows how related its parents are', function () {
    $this->actingAs(User::factory()->create());
    $sire = dg('Sire', 'male');
    $dam = dg('Dam', 'female');
    $a = dg('A', 'male', ['sire_id' => $sire->id, 'dam_id' => $dam->id]);
    $b = dg('B', 'female', ['sire_id' => $sire->id, 'dam_id' => $dam->id]);
    $litter = Litter::create(['status' => 'planned', 'sire_id' => $a->id, 'dam_id' => $b->id]);

    $props = $this->get(route('admin.litters.edit', $litter))->viewData('page')['props'];

    expect($props['inbreeding']['percent'])->toBe(25.0);
});
