<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CoatColor;
use App\Enums\DogLeftReason;
use App\Enums\DogRole;
use App\Enums\DogSex;
use App\Http\Controllers\Admin\Concerns\CleansInput;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Dog;
use App\Models\Litter;
use App\Support\AkcChecks;
use App\Support\Pedigree;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DogController extends Controller
{
    use CleansInput;

    public function index(): Response
    {
        return Inertia::render('admin/dogs/Index', [
            'dogs' => Dog::with(['photos' => fn ($q) => $q->limit(1), 'ownerContact:id,name'])
                ->orderBy('sort_order')->orderBy('name')->get(),
            'archived' => Dog::onlyTrashed()->orderBy('name')->get(['id', 'slug', 'name', 'left_on', 'deleted_at'])
                ->map(fn (Dog $d) => [...$d->toArray(), 'purge_blocked_until' => $d->purgeBlockedUntil()?->toDateString()]),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('admin/dogs/Edit', [
            'dog' => null,
            'outside' => $request->boolean('outside'),
            ...$this->choices(),
        ]);
    }

    /** Add a dog from another kennel (a sire or dam we do not own) without leaving the page. */
    public function quick(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('dogs', 'name')->whereNull('deleted_at')],
            'registered_name' => ['nullable', 'string', 'max:200'],
            'registry_number' => ['nullable', 'string', 'max:100'],
            'sex' => ['required', Rule::enum(DogSex::class)],
            'owner_contact_id' => ['nullable', 'exists:contacts,id'],
        ], [
            'name.unique' => 'You already have a dog with that name. Add something to tell them apart, like the kennel name.',
        ]);

        $dog = Dog::create($data + ['is_ours' => false, 'role' => DogRole::Other, 'is_public' => false]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$dog->name} added as a dog from another kennel."]);

        return back();
    }

    /** @return array<string, mixed> */
    private function choices(?Dog $except = null): array
    {
        return [
            'parents' => Dog::when($except, fn ($q) => $q->whereKeyNot($except->id))->orderBy('name')
                ->get(['id', 'name', 'sex', 'is_ours', 'registered_name']),
            'contacts' => Contact::orderBy('name')->get(['id', 'name']),
        ];
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
            'dog' => $dog->load(['healthTests', 'heatCycles', 'photos', 'documents', 'healthRecords', 'achievements'])->setAttribute('purge_blocked_until', $dog->trashed() ? $dog->purgeBlockedUntil()?->toDateString() : null),
            'pedigree' => Pedigree::build($dog, 3),
            'akc' => AkcChecks::forDog($dog),
            'litters' => Litter::where('sire_id', $dog->id)->orWhere('dam_id', $dog->id)
                ->withCount('puppies')->orderByDesc('born_on')->orderByDesc('due_on')->get()
                ->map(fn (Litter $l) => ['id' => $l->id, 'slug' => $l->slug, 'title' => $l->title, 'status' => $l->status->value, 'born_on' => $l->born_on?->toDateString(), 'due_on' => $l->due_on?->toDateString(), 'puppies_count' => $l->puppies_count]),
            ...$this->choices($dog),
        ]);
    }

    public function update(Request $request, Dog $dog): RedirectResponse
    {
        $data = $this->validated($request);

        // Once titles are listed under Titles & awards, that list is the source of the Titles box.
        if ($dog->achievements()->exists()) {
            unset($data['titles']);
        }

        $dog->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saved.']);

        return to_route('admin.dogs.edit', $dog->fresh());
    }

    /** Archive, not erase: AKC asks for breeding and ownership records to be kept for 5 years. */
    public function destroy(Dog $dog): RedirectResponse
    {
        $dog->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$dog->name} archived. Their records are kept, and you can bring them back from the Dogs page."]);

        return to_route('admin.dogs.index');
    }

    public function restore(Dog $dog): RedirectResponse
    {
        $dog->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$dog->name} is back."]);

        return to_route('admin.dogs.edit', $dog);
    }

    /** Permanent delete, only once the 5 year record-keeping period is over (or the dog was added by mistake). */
    public function purge(Dog $dog): RedirectResponse
    {
        if ($until = $dog->purgeBlockedUntil()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => "AKC asks you to keep this dog’s records until {$until->format('M j, Y')}. You can delete it for good after that date."]);

            return back();
        }

        $dog->forceDelete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Deleted for good.']);

        return to_route('admin.dogs.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $this->cleanDigits($request, ['microchip']);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100', Rule::unique('dogs', 'name')->ignore($request->route('dog'))->whereNull('deleted_at')],
            'registered_name' => ['nullable', 'string', 'max:200'],
            'sex' => ['required', Rule::enum(DogSex::class)],
            'role' => ['required', Rule::enum(DogRole::class)],
            'color' => ['nullable', Rule::enum(CoatColor::class)],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'sire_id' => ['nullable', 'exists:dogs,id'],
            'dam_id' => ['nullable', 'exists:dogs,id'],
            'is_ours' => ['boolean'],
            'owner_contact_id' => ['nullable', 'exists:contacts,id'],
            'titles' => ['nullable', 'string', 'max:200'],
            'dna_profile' => ['nullable', 'string', 'max:60'],
            'co_owner_name' => ['nullable', 'string', 'max:150'],
            'acquired_on' => ['nullable', 'date', 'before_or_equal:today'],
            'left_on' => ['nullable', 'date', 'before_or_equal:today'],
            'left_reason' => ['nullable', Rule::enum(DogLeftReason::class), 'required_with:left_on'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'is_public' => ['boolean'],
            'registry_number' => ['nullable', 'string', 'max:100'],
            'microchip' => ['nullable', 'regex:/^[0-9]{9,15}$/'],
            'private_notes' => ['nullable', 'string', 'max:10000'],
        ], [
            'name.unique' => 'You already have a dog with that name. Add something to tell them apart, like a last initial.',
            'left_reason.required_with' => 'Say why the dog left the kennel: sold, given away or passed away.',
            'microchip.regex' => 'A microchip number is 9 to 15 digits, with no letters. Check the number on the vet paperwork.',
        ]);

        $validator->after(function ($v) use ($request) {
            $in = $v->getData();
            $self = $request->route('dog');

            foreach (['sire_id' => [DogSex::Male, 'sire (father)', 'male'], 'dam_id' => [DogSex::Female, 'dam (mother)', 'female']] as $key => [$sex, $label, $word]) {
                $parent = filled($in[$key] ?? null) ? Dog::find($in[$key]) : null;

                if (! $parent) {
                    continue;
                }

                if ($self && $parent->id === $self->id) {
                    $v->errors()->add($key, 'A dog cannot be its own parent.');
                } elseif ($parent->sex !== $sex) {
                    $v->errors()->add($key, "{$parent->name} is a {$parent->sex->value}, but the {$label} must be a {$word}.");
                } elseif ($self && Pedigree::hasAncestor($parent->id, $self->id)) {
                    $v->errors()->add($key, "{$parent->name} is already a descendant of this dog, so cannot also be a parent. Check the family tree.");
                }
            }

            if (filled($in['left_on'] ?? null) && filled($in['acquired_on'] ?? null) && $in['left_on'] < $in['acquired_on']) {
                $v->errors()->add('left_on', 'The dog cannot leave before it arrived. Check the two dates.');
            }

            if (($in['role'] ?? null) === DogRole::Stud->value && ($in['sex'] ?? null) === DogSex::Female->value) {
                $v->errors()->add('role', 'A stud is a father, so the dog must be male. Change the sex, or choose another role.');
            }

            if (($in['role'] ?? null) === DogRole::Dam->value && ($in['sex'] ?? null) === DogSex::Male->value) {
                $v->errors()->add('role', 'A dam is a mother, so the dog must be female. Change the sex, or choose another role.');
            }
        });

        return $validator->validate();
    }
}
