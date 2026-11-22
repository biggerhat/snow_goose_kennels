<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CoatColor;
use App\Enums\DogSex;
use App\Enums\LitterStatus;
use App\Enums\PuppyStatus;
use App\Http\Controllers\Admin\Concerns\CleansInput;
use App\Http\Controllers\Controller;
use App\Models\Litter;
use App\Models\Puppy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PuppyController extends Controller
{
    use CleansInput;

    public function store(Request $request, Litter $litter): RedirectResponse
    {
        $litter->puppies()->create($this->validated($request));

        return back();
    }

    public function update(Request $request, Puppy $puppy): RedirectResponse
    {
        $puppy->update($this->validated($request));
        $this->syncLitterStatus($puppy->litter);

        return back();
    }

    public function destroy(Puppy $puppy): RedirectResponse
    {
        $litter = $puppy->litter;
        $puppy->delete();
        $this->syncLitterStatus($litter);

        return back();
    }

    /** When every puppy has gone home (or been kept), the litter is done. */
    private function syncLitterStatus(Litter $litter): void
    {
        $statuses = $litter->puppies()->pluck('status');

        if ($litter->status === LitterStatus::OnGround
            && $statuses->isNotEmpty()
            && $statuses->every(fn ($s) => in_array($s, [PuppyStatus::Home, PuppyStatus::Kept], true))) {
            $litter->update(['status' => LitterStatus::AllHome]);
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Every puppy is home, so the litter is now marked "All gone home".']);
        }
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $this->cleanMoney($request, ['sale_price', 'birth_weight_g']);

        return $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'collar' => ['nullable', 'string', 'max:50'],
            'sex' => ['required', Rule::enum(DogSex::class)],
            'color' => ['nullable', Rule::enum(CoatColor::class)],
            'birth_weight_g' => ['nullable', 'integer', 'min:50', 'max:2000'],
            'status' => ['required', Rule::enum(PuppyStatus::class)],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'sale_price' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
