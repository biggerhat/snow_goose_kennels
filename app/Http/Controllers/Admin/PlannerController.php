<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dog;
use App\Models\Litter;
use App\Support\AkcChecks;
use App\Support\Inbreeding;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** "What if I bred these two?": how related they are, and what each side brings. */
class PlannerController extends Controller
{
    private const CLEARANCES = [
        'Hips' => '/hip/i',
        'Elbows' => '/elbow/i',
        'Eyes' => '/eye|caer|acvo/i',
        'EIC' => '/eic/i',
        'D-locus' => '/d-?locus|dilute/i',
    ];

    public function index(Request $request): Response
    {
        $sire = $request->filled('sire') ? Dog::where('sex', 'male')->find($request->integer('sire')) : null;
        $dam = $request->filled('dam') ? Dog::where('sex', 'female')->find($request->integer('dam')) : null;

        return Inertia::render('admin/Planner', [
            'dogs' => Dog::orderBy('name')->get(['id', 'name', 'sex', 'is_ours']),
            'selected' => ['sire' => $sire?->id, 'dam' => $dam?->id],
            'result' => $sire && $dam ? $this->evaluate($sire, $dam) : null,
        ]);
    }

    /** @return array<string, mixed> */
    private function evaluate(Dog $sire, Dog $dam): array
    {
        $litter = (new Litter(['status' => 'planned', 'sire_id' => $sire->id, 'dam_id' => $dam->id, 'bred_on' => today()]))
            ->setRelation('sire', $sire)->setRelation('dam', $dam);

        $checks = collect(AkcChecks::forLitter($litter)['items'])->whereIn('key', ['parents', 'dam_age', 'sire_age', 'dna']);

        return [
            'inbreeding' => Inbreeding::forMating($sire, $dam),
            'parents' => [$this->summary($sire), $this->summary($dam)],
            'checks' => $checks->values()->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function summary(Dog $dog): array
    {
        $tests = $dog->healthTests()->get();

        return [
            'id' => $dog->id,
            'slug' => $dog->slug,
            'name' => $dog->name,
            'registered_name' => $dog->registered_name,
            'titles' => $dog->titles,
            'sex' => $dog->sex->value,
            'is_ours' => $dog->is_ours,
            'age_label' => $dog->age_label,
            'registry_number' => $dog->registry_number,
            'clearances' => collect(self::CLEARANCES)->map(fn ($re, $label) => ['label' => $label, 'done' => $tests->contains(fn ($t) => preg_match($re, $t->name) === 1)])->values()->all(),
        ];
    }
}
