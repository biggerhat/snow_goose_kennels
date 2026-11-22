<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CoatColor;
use App\Enums\DogRole;
use App\Enums\DogSex;
use App\Http\Controllers\Admin\Concerns\CleansInput;
use App\Http\Controllers\Controller;
use App\Models\Dog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DogController extends Controller
{
    use CleansInput;

    public function index(): Response
    {
        return Inertia::render('admin/dogs/Index', [
            'dogs' => Dog::with(['photos' => fn ($q) => $q->limit(1)])
                ->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/dogs/Edit', ['dog' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dog = Dog::create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$dog->name} added."]);

        return to_route('admin.dogs.edit', $dog);
    }

    public function edit(Dog $dog): Response
    {
        return Inertia::render('admin/dogs/Edit', [
            'dog' => $dog->load(['healthTests', 'heatCycles', 'photos']),
        ]);
    }

    public function update(Request $request, Dog $dog): RedirectResponse
    {
        $dog->update($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saved.']);

        return to_route('admin.dogs.edit', $dog->fresh());
    }

    public function destroy(Dog $dog): RedirectResponse
    {
        $dog->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Dog removed.']);

        return to_route('admin.dogs.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $this->cleanDigits($request, ['microchip']);

        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('dogs', 'name')->ignore($request->route('dog'))],
            'registered_name' => ['nullable', 'string', 'max:200'],
            'sex' => ['required', Rule::enum(DogSex::class)],
            'role' => ['required', Rule::enum(DogRole::class)],
            'color' => ['nullable', Rule::enum(CoatColor::class)],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'is_public' => ['boolean'],
            'registry_number' => ['nullable', 'string', 'max:100'],
            'microchip' => ['nullable', 'regex:/^[0-9]{9,15}$/'],
            'private_notes' => ['nullable', 'string', 'max:10000'],
        ], [
            'name.unique' => 'You already have a dog with that name. Add something to tell them apart, like a last initial.',
            'microchip.regex' => 'A microchip number is 9 to 15 digits, with no letters. Check the number on the vet paperwork.',
        ]);
    }
}
