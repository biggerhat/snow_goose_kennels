<?php

namespace App\Support;

use App\Models\Expense;
use App\Models\Litter;
use App\Models\Puppy;

/**
 * What came in and what went out in a year. Income and stud fees come from the dog and litter pages,
 * so nothing is typed twice; everything else is an expense she adds. All amounts are whole cents.
 */
class MoneySummary
{
    /** @return array{income: int, costs: int, net: int, income_lines: list<array{label: string, cents: int}>, categories: list<array{label: string, cents: int}>, litters: list<array<string, mixed>>} */
    public static function forYear(int $year): array
    {
        $sales = Puppy::query()->with('litter.sire')->whereNotNull('sale_price')->where('status', 'home')->get()
            ->filter(fn (Puppy $p) => self::saleYear($p) === $year);
        $studIncome = Litter::withTrashed()->with('sire')->whereNotNull('stud_fee')->whereNotNull('stud_fee_paid_on')->whereYear('stud_fee_paid_on', $year)->get()
            ->filter(fn (Litter $l) => $l->sire?->is_ours);
        $studCosts = Litter::withTrashed()->with('sire')->whereNotNull('stud_fee')->whereNotNull('stud_fee_paid_on')->whereYear('stud_fee_paid_on', $year)->get()
            ->filter(fn (Litter $l) => $l->sire && ! $l->sire->is_ours);
        $expenses = Expense::with('litter')->whereYear('spent_on', $year)->get();

        $salesCents = (int) $sales->sum('sale_price') * 100;
        $studInCents = (int) $studIncome->sum('stud_fee') * 100;
        $studOutCents = (int) $studCosts->sum('stud_fee') * 100;
        $expenseCents = (int) $expenses->sum('amount_cents');

        $categories = $expenses->groupBy(fn (Expense $e) => $e->category->label())->map(fn ($g, $label) => ['label' => $label, 'cents' => (int) $g->sum('amount_cents')])->values()->all();

        if ($studOutCents) {
            $categories[] = ['label' => 'Stud fees paid to other kennels', 'cents' => $studOutCents];
        }

        usort($categories, fn ($a, $b) => $b['cents'] <=> $a['cents']);

        $litterIds = $sales->pluck('litter_id')->merge($expenses->pluck('litter_id'))->merge($studCosts->pluck('id'))->merge($studIncome->pluck('id'))->filter()->unique();
        $litters = Litter::withTrashed()->whereIn('id', $litterIds)->get()->map(function (Litter $l) use ($sales, $expenses, $studCosts, $studIncome) {
            $in = (int) $sales->where('litter_id', $l->id)->sum('sale_price') * 100 + (int) $studIncome->where('id', $l->id)->sum('stud_fee') * 100;
            $out = (int) $expenses->where('litter_id', $l->id)->sum('amount_cents') + (int) $studCosts->where('id', $l->id)->sum('stud_fee') * 100;

            return ['id' => $l->id, 'slug' => $l->slug, 'title' => $l->title, 'income' => $in, 'costs' => $out, 'net' => $in - $out, 'puppies_sold' => $sales->where('litter_id', $l->id)->count()];
        })->sortByDesc('income')->values()->all();

        $incomeLines = array_values(array_filter([
            ['label' => 'Puppy sales ('.$sales->count().')', 'cents' => $salesCents],
            ['label' => 'Stud fees received', 'cents' => $studInCents],
        ], fn ($l) => $l['cents'] > 0));

        return [
            'income' => $salesCents + $studInCents,
            'costs' => $expenseCents + $studOutCents,
            'net' => $salesCents + $studInCents - $expenseCents - $studOutCents,
            'income_lines' => $incomeLines,
            'categories' => $categories,
            'litters' => $litters,
        ];
    }

    /** The day the puppy went home, or failing that the day the record was last touched: when the money came in, not when the litter was born. */
    private static function saleYear(Puppy $p): ?int
    {
        return ($p->went_home_on ?? $p->updated_at)?->year;
    }

    /** @return list<int> Years that have anything in them, newest first, always including this year. */
    public static function years(): array
    {
        $years = collect([now()->year]);

        foreach (Expense::pluck('spent_on') as $d) {
            $years->push($d->year);
        }

        foreach (Puppy::whereNotNull('sale_price')->where('status', 'home')->with('litter')->get() as $p) {
            if ($y = self::saleYear($p)) {
                $years->push($y);
            }
        }

        return $years->unique()->sortDesc()->values()->all();
    }
}
