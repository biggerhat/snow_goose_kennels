<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Dog;
use App\Models\KennelProfile;
use App\Models\Litter;
use App\Models\Puppy;
use App\Support\AkcPrograms;
use App\Support\Kennel;
use App\Support\Readiness;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/** The AKC-style record book: one printable page per dog and per litter. */
class RecordsController extends Controller
{
    public function index(): Response
    {
        $kennel = KennelProfile::instance();

        return Inertia::render('admin/Records', [
            'readiness' => Readiness::report(),
            'programs' => AkcPrograms::report(),
            'kennelId' => $kennel->id,
            'kennelDocuments' => $kennel->documents()->get(),
            'dogs' => Dog::withTrashed()->with('ownerContact:id,name')->orderBy('name')
                ->get(['id', 'slug', 'name', 'registry_number', 'dna_profile', 'microchip', 'birth_date', 'acquired_on', 'left_on', 'is_ours', 'owner_contact_id', 'deleted_at']),
            'litters' => Litter::withTrashed()->with(['sire:id,name', 'dam:id,name'])->withCount('puppies')->orderByDesc('born_on')->orderByDesc('due_on')->get()
                ->map(fn (Litter $l) => [
                    'id' => $l->id, 'slug' => $l->slug, 'title' => $l->title, 'status' => $l->status->value,
                    'born_on' => $l->born_on?->toDateString(), 'due_on' => $l->due_on?->toDateString(),
                    'akc_litter_number' => $l->akc_litter_number, 'archived' => $l->trashed(), 'akc_applied_on' => $l->akc_applied_on?->toDateString(),
                    'puppies_count' => $l->puppies_count,
                ]),
        ]);
    }

    /** A printable worksheet for the written emergency plan AKC asks breeders to keep. */
    public function emergencyPlan(): View
    {
        return view('records.emergency-plan', [
            'owner' => Kennel::owner(),
            'vets' => Contact::where('type', 'vet')->orderBy('name')->get(),
            'dogCount' => Dog::where('is_ours', true)->whereNull('left_on')->count(),
            'puppyCount' => Puppy::whereHas('litter', fn ($q) => $q->where('status', 'on_ground'))->whereNotIn('status', ['home', 'kept'])->count(),
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
        $litters = Litter::withTrashed()->orderByDesc('born_on')->get()->each(fn (Litter $l) => $this->loadLitter($l));

        return view('records.all', [
            'dogs' => Dog::withTrashed()->orderBy('name')->get()->each(fn (Dog $d) => $this->loadDog($d)),
            'litters' => $litters,
            'litterMap' => Dog::withTrashed()->pluck('id')->mapWithKeys(fn ($id) => [$id => $litters->filter(fn (Litter $l) => in_array($id, [$l->sire_id, $l->dam_id], true))->values()]),
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
        return Litter::withTrashed()->where(fn ($q) => $q->where('sire_id', $dog->id)->orWhere('dam_id', $dog->id))
            ->with(['sire', 'dam', 'puppies'])->orderBy('born_on')->get();
    }
}
