<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dog;
use App\Models\Litter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/** The AKC-style record book: one printable page per dog and per litter. */
class RecordsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/Records', [
            'dogs' => Dog::with('ownerContact:id,name')->orderBy('name')
                ->get(['id', 'slug', 'name', 'registry_number', 'dna_profile', 'microchip', 'birth_date', 'acquired_on', 'left_on', 'is_ours', 'owner_contact_id']),
            'litters' => Litter::with(['sire:id,name', 'dam:id,name'])->withCount('puppies')->orderByDesc('born_on')->orderByDesc('due_on')->get()
                ->map(fn (Litter $l) => [
                    'id' => $l->id, 'slug' => $l->slug, 'title' => $l->title, 'status' => $l->status->value,
                    'born_on' => $l->born_on?->toDateString(), 'due_on' => $l->due_on?->toDateString(),
                    'akc_litter_number' => $l->akc_litter_number, 'akc_applied_on' => $l->akc_applied_on?->toDateString(),
                    'puppies_count' => $l->puppies_count,
                ]),
        ]);
    }

    public function dog(Dog $dog): View
    {
        return view('records.dog', ['dog' => $this->loadDog($dog), 'litters' => $this->littersOf($dog)]);
    }

    public function litter(Litter $litter): View
    {
        return view('records.litter', ['litter' => $this->loadLitter($litter)]);
    }

    public function all(): View
    {
        $litters = Litter::orderByDesc('born_on')->get()->each(fn (Litter $l) => $this->loadLitter($l));

        return view('records.all', [
            'dogs' => Dog::orderBy('name')->get()->each(fn (Dog $d) => $this->loadDog($d)),
            'litters' => $litters,
            'litterMap' => Dog::pluck('id')->mapWithKeys(fn ($id) => [$id => $litters->filter(fn (Litter $l) => in_array($id, [$l->sire_id, $l->dam_id], true))->values()]),
        ]);
    }

    private function loadDog(Dog $dog): Dog
    {
        return $dog->load(['sire', 'dam', 'ownerContact', 'heatCycles', 'healthTests', 'healthRecords', 'documents']);
    }

    private function loadLitter(Litter $litter): Litter
    {
        return $litter->load(['sire', 'dam', 'puppies.buyer', 'puppies.healthRecords']);
    }

    /** @return Collection<int, Litter> */
    private function littersOf(Dog $dog): Collection
    {
        return Litter::where('sire_id', $dog->id)->orWhere('dam_id', $dog->id)
            ->with(['sire', 'dam', 'puppies'])->orderBy('born_on')->get();
    }
}
