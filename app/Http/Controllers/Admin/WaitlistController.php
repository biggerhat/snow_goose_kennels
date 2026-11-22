<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CoatColor;
use App\Enums\ContactType;
use App\Enums\DogSex;
use App\Enums\WaitlistStatus;
use App\Http\Controllers\Admin\Concerns\CleansInput;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Litter;
use App\Models\WaitlistEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WaitlistController extends Controller
{
    use CleansInput;

    public function index(): Response
    {
        return Inertia::render('admin/waitlist/Index', [
            'entries' => WaitlistEntry::with(['contact:id,name,phone,email', 'litter.sire:id,name', 'litter.dam:id,name', 'puppy:id,name,collar'])
                ->orderBy('pick_order')->oldest()->get()
                ->each(fn ($e) => $e->litter?->append('title')),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('admin/waitlist/Edit', [
            'entry' => null,
            'contacts' => Contact::orderBy('name')->get(['id', 'name']),
            'litters' => $this->litters(),
            'presetContact' => $request->integer('contact') ?: null,
            'presetLitter' => $request->integer('litter') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if (empty($data['contact_id'])) {
            $contact = Contact::create([
                'name' => $data['new_name'],
                'email' => $data['new_email'] ?? null,
                'phone' => $data['new_phone'] ?? null,
                'type' => ContactType::Buyer,
            ]);
            $data['contact_id'] = $contact->id;
        }

        unset($data['new_name'], $data['new_email'], $data['new_phone']);
        WaitlistEntry::create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Added to the waitlist.']);

        return to_route('admin.waitlist.index');
    }

    public function edit(WaitlistEntry $waitlist): Response
    {
        return Inertia::render('admin/waitlist/Edit', [
            'entry' => $waitlist->load('contact:id,name'),
            'contacts' => Contact::orderBy('name')->get(['id', 'name']),
            'litters' => $this->litters(),
            'puppies' => $waitlist->litter?->puppies()->get(['id', 'name', 'collar', 'sex']) ?? [],
        ]);
    }

    public function update(Request $request, WaitlistEntry $waitlist): RedirectResponse
    {
        $data = $this->validated($request, creating: false);
        unset($data['new_name'], $data['new_email'], $data['new_phone']);

        $waitlist->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saved.']);

        return to_route('admin.waitlist.index');
    }

    public function destroy(WaitlistEntry $waitlist): RedirectResponse
    {
        $waitlist->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Removed from the waitlist.']);

        return to_route('admin.waitlist.index');
    }

    private function litters()
    {
        return Litter::with(['sire:id,name', 'dam:id,name'])->latest('due_on')->get()
            ->map(fn ($l) => ['id' => $l->id, 'title' => $l->title]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, bool $creating = true): array
    {
        $this->cleanMoney($request, ['deposit_amount']);

        $validator = Validator::make($request->all(), [
            'contact_id' => $creating
                ? ['nullable', 'exists:contacts,id', 'required_without:new_name']
                : ['required', 'exists:contacts,id'],
            'new_name' => ['nullable', 'string', 'max:150'],
            'new_email' => ['nullable', 'email', 'max:200'],
            'new_phone' => ['nullable', 'string', 'max:50'],
            'litter_id' => ['nullable', 'exists:litters,id'],
            'puppy_id' => ['nullable', 'exists:puppies,id'],
            'preferred_sex' => ['nullable', Rule::enum(DogSex::class)],
            'preferred_color' => ['nullable', Rule::enum(CoatColor::class)],
            'pick_order' => ['nullable', 'integer', 'min:1', 'max:99'],
            'status' => ['required', Rule::enum(WaitlistStatus::class)],
            'deposit_amount' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'deposit_paid_on' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'contact_id.required_without' => 'Choose a person from the list, or type a new name below.',
        ]);

        $validator->after(function ($v) use ($request) {
            $in = $v->getData();

            if (filled($in['pick_order'] ?? null) && filled($in['litter_id'] ?? null)) {
                $taken = WaitlistEntry::where('litter_id', $in['litter_id'])
                    ->where('pick_order', $in['pick_order'])
                    ->when($request->route('waitlist'), fn ($q, $e) => $q->whereKeyNot($e->getKey()))
                    ->with('contact:id,name')->first();

                if ($taken) {
                    $v->errors()->add('pick_order', "Pick #{$in['pick_order']} for this litter already belongs to {$taken->contact->name}. Choose a different number.");
                }
            }

            if (filled($in['deposit_paid_on'] ?? null) && ! filled($in['deposit_amount'] ?? null)) {
                $v->errors()->add('deposit_amount', 'You entered the date the deposit was paid. How much was it?');
            }

            if (! empty($in['puppy_id']) && empty($in['litter_id'])) {
                $v->errors()->add('puppy_id', 'Choose the litter first, then the puppy.');
            }
        });

        return $validator->validate();
    }
}
