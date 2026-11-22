<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExpenseCategory;
use App\Http\Controllers\Controller;
use App\Models\Dog;
use App\Models\Expense;
use App\Models\Litter;
use App\Support\MoneySummary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        $year = (int) $request->integer('year', now()->year);

        return Inertia::render('admin/Money', [
            'year' => $year,
            'years' => MoneySummary::years(),
            'summary' => MoneySummary::forYear($year),
            'expenses' => Expense::with(['litter', 'dog:id,name'])->whereYear('spent_on', $year)->orderByDesc('spent_on')->orderByDesc('id')->get()
                ->map(fn (Expense $e) => [
                    'id' => $e->id, 'spent_on' => $e->spent_on->toDateString(), 'category' => $e->category->value,
                    'amount' => $e->amount_cents / 100, 'vendor' => $e->vendor, 'description' => $e->description,
                    'litter_id' => $e->litter_id, 'dog_id' => $e->dog_id,
                    'litter_title' => $e->litter?->title, 'dog_name' => $e->dog?->name,
                ]),
            'litters' => Litter::withTrashed()->orderByDesc('born_on')->orderByDesc('id')->get()->map(fn (Litter $l) => ['id' => $l->id, 'title' => $l->title]),
            'dogs' => Dog::where('is_ours', true)->orderBy('name')->get(['id', 'name']),
            'categories' => ExpenseCategory::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Expense::create($this->validated($request));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Expense added.']);

        return back();
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $expense->update($this->validated($request));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Expense saved.']);

        return back();
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Expense removed.']);

        return back();
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        // Dollars and cents as people type them: "$1,234.50".
        $raw = preg_replace('/[\s$,]/', '', (string) $request->input('amount'));
        $request->merge(['amount' => $raw === '' ? null : $raw]);

        $data = $request->validate([
            'spent_on' => ['required', 'date', 'before_or_equal:today'],
            'category' => ['required', Rule::enum(ExpenseCategory::class)],
            'amount' => ['required', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'vendor' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:300'],
            'litter_id' => ['nullable', 'exists:litters,id'],
            'dog_id' => ['nullable', 'exists:dogs,id'],
        ], [
            'spent_on.required' => 'Pick the day you spent it.',
            'spent_on.before_or_equal' => 'That date is in the future. Use the day you actually paid.',
            'category.required' => 'Choose what kind of expense it was.',
            'amount.required' => 'Enter how much it cost.',
            'amount.regex' => 'Enter the amount in dollars, like 45 or 45.50.',
        ]);

        $data['amount_cents'] = (int) round(((float) $data['amount']) * 100);
        unset($data['amount']);

        return $data;
    }
}
