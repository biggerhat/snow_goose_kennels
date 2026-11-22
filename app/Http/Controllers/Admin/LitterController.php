<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LitterStatus;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Dog;
use App\Models\Litter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LitterController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/litters/Index', [
            'litters' => Litter::with(['sire:id,name', 'dam:id,name'])
                ->withCount(['puppies', 'waitlistEntries'])
                ->orderByDesc('due_on')->orderByDesc('born_on')->get()
                ->append('title'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/litters/Edit', [
            'litter' => null,
            'dogs' => $this->dogs(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $litter = Litter::create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Litter created.']);

        return to_route('admin.litters.edit', $litter);
    }

    public function edit(Litter $litter): Response
    {
        $litter->load(['puppies.buyer:id,name', 'puppies.weights', 'diary', 'photos', 'waitlistEntries.contact:id,name,phone,email'])
            ->append('title');

        return Inertia::render('admin/litters/Edit', [
            'litter' => $litter,
            'dogs' => $this->dogs(),
            'contacts' => Contact::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Litter $litter): RedirectResponse
    {
        $litter->update($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saved.']);

        return to_route('admin.litters.edit', $litter->fresh());
    }

    public function destroy(Litter $litter): RedirectResponse
    {
        $litter->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Litter removed.']);

        return to_route('admin.litters.index');
    }

    private function dogs()
    {
        return Dog::orderBy('name')->get(['id', 'name', 'sex']);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['nullable', 'string', 'max:150'],
            'sire_id' => ['nullable', 'exists:dogs,id'],
            'dam_id' => ['nullable', 'exists:dogs,id'],
            'status' => ['required', Rule::enum(LitterStatus::class)],
            'bred_on' => ['nullable', 'date'],
            'due_on' => ['nullable', 'date'],
            'born_on' => ['nullable', 'date'],
            'go_home_on' => ['nullable', 'date'],
            'price' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'deposit' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'expected_count' => ['nullable', 'integer', 'min:0', 'max:30'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_public' => ['boolean'],
            'private_notes' => ['nullable', 'string', 'max:10000'],
        ]);
    }
}
