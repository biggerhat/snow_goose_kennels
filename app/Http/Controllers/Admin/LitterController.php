<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BreedingMethod;
use App\Enums\DogSex;
use App\Enums\LitterStatus;
use App\Http\Controllers\Admin\Concerns\CleansInput;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Dog;
use App\Models\Litter;
use App\Support\AkcChecks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LitterController extends Controller
{
    use CleansInput;

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
            'contacts' => Contact::orderBy('name')->get(['id', 'name']),
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
        $litter->load(['puppies.buyer:id,name', 'puppies.weights', 'puppies.documents', 'puppies.healthRecords', 'diary', 'photos', 'documents', 'waitlistEntries.contact:id,name,phone,email'])
            ->append('title');

        return Inertia::render('admin/litters/Edit', [
            'litter' => $litter,
            'dogs' => $this->dogs(),
            'contacts' => Contact::orderBy('name')->get(['id', 'name']),
            'akc' => AkcChecks::forLitter($litter),
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
        return Dog::orderBy('name')->get(['id', 'name', 'sex', 'is_ours']);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $this->cleanMoney($request, ['price', 'deposit', 'stud_fee']);

        $validator = Validator::make($request->all(), [
            'name' => ['nullable', 'string', 'max:150'],
            'sire_id' => ['nullable', 'exists:dogs,id', 'different:dam_id'],
            'dam_id' => ['nullable', 'exists:dogs,id'],
            'status' => ['required', Rule::enum(LitterStatus::class)],
            'bred_on' => ['nullable', 'date', 'before_or_equal:today'],
            'due_on' => ['nullable', 'date'],
            'born_on' => ['nullable', 'date', 'before_or_equal:today'],
            'go_home_on' => ['nullable', 'date'],
            'price' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'deposit' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'expected_count' => ['nullable', 'integer', 'min:0', 'max:30'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_public' => ['boolean'],
            'private_notes' => ['nullable', 'string', 'max:10000'],
            'breeding_method' => ['nullable', Rule::enum(BreedingMethod::class)],
            'bred_place' => ['nullable', 'string', 'max:150'],
            'bred_handler' => ['nullable', 'string', 'max:150'],
            'stud_fee' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'stud_fee_paid_on' => ['nullable', 'date', 'before_or_equal:today'],
            'stillborn_count' => ['nullable', 'integer', 'min:0', 'max:30'],
            'akc_applied_on' => ['nullable', 'date', 'before_or_equal:today'],
            'akc_litter_number' => ['nullable', 'string', 'max:40'],
        ], [
            'sire_id.different' => 'The sire and the dam must be two different dogs.',
        ]);

        $validator->after(function ($v) {
            $in = $v->getData();
            $date = fn (string $k) => filled($in[$k] ?? null) ? Carbon::parse($in[$k]) : null;

            foreach (['sire_id' => [DogSex::Male, 'sire (father)', 'male'], 'dam_id' => [DogSex::Female, 'dam (mother)', 'female']] as $key => [$sex, $label, $word]) {
                $dog = filled($in[$key] ?? null) ? Dog::find($in[$key]) : null;

                if ($dog && $dog->sex !== $sex) {
                    $v->errors()->add($key, "{$dog->name} is marked as {$dog->sex->value}, but the {$label} should be a {$word} dog. Pick another dog, or fix the dog's sex on its page.");
                }
            }

            [$bred, $due, $born, $home] = [$date('bred_on'), $date('due_on'), $date('born_on'), $date('go_home_on')];

            if ($bred && $due && $due->lt($bred)) {
                $v->errors()->add('due_on', 'The due date cannot be before the date bred.');
            }

            if ($bred && $born && $born->lt($bred)) {
                $v->errors()->add('born_on', 'Puppies cannot be born before the date bred.');
            }

            if ($born && $home && $home->lt($born)) {
                $v->errors()->add('go_home_on', 'The go-home date cannot be before the puppies were born.');
            }

            if (in_array($in['status'] ?? null, [LitterStatus::OnGround->value, LitterStatus::AllHome->value], true) && ! $born) {
                $v->errors()->add('born_on', 'Add the date the puppies were born, because this litter is marked as born.');
            }

            if ($born && filled($in['akc_applied_on'] ?? null) && Carbon::parse($in['akc_applied_on'])->lt($born)) {
                $v->errors()->add('akc_applied_on', 'The AKC application cannot be sent before the puppies were born.');
            }

            if (filled($in['price'] ?? null) && filled($in['deposit'] ?? null) && (int) $in['deposit'] > (int) $in['price']) {
                $v->errors()->add('deposit', 'The deposit is more than the price. Check both amounts.');
            }
        });

        return $validator->validate();
    }
}
