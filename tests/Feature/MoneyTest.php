<?php

use App\Models\Dog;
use App\Models\Expense;
use App\Models\Litter;
use App\Models\User;
use App\Support\MoneySummary;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('adds an expense typed the way people type money', function () {
    $this->post(route('admin.expenses.store'), ['spent_on' => '2026-03-02', 'category' => 'vet', 'amount' => '$1,234.50', 'vendor' => 'Dr. Smith'])->assertRedirect();

    expect(Expense::first())->amount_cents->toBe(123450)->vendor->toBe('Dr. Smith');
});

it('explains a missing amount, a bad amount and a future date in plain words', function () {
    $r = $this->post(route('admin.expenses.store'), ['spent_on' => today()->addDay()->toDateString(), 'category' => '', 'amount' => 'lots']);

    $r->assertSessionHasErrors(['spent_on', 'category', 'amount']);
    expect(session('errors')->first('amount'))->toContain('dollars')->and(session('errors')->first('spent_on'))->toContain('future');
});

it('edits and removes an expense', function () {
    $e = Expense::create(['spent_on' => '2026-03-02', 'category' => 'food', 'amount_cents' => 5000]);

    $this->put(route('admin.expenses.update', $e), ['spent_on' => '2026-03-02', 'category' => 'food', 'amount' => '60'])->assertRedirect();
    expect($e->fresh()->amount_cents)->toBe(6000);

    $this->delete(route('admin.expenses.destroy', $e));
    expect(Expense::count())->toBe(0);
});

it('works out income and costs for a year from puppies, stud fees and expenses', function () {
    $ours = Dog::create(['name' => 'Duke', 'sex' => 'male', 'role' => 'stud', 'is_ours' => true]);
    $outside = Dog::create(['name' => 'Rex', 'sex' => 'male', 'role' => 'other', 'is_ours' => false]);
    $dam = Dog::create(['name' => 'Bella', 'sex' => 'female', 'role' => 'dam']);

    $litter = Litter::create(['sire_id' => $outside->id, 'dam_id' => $dam->id, 'status' => 'all_home', 'born_on' => '2026-02-01', 'stud_fee' => 800, 'stud_fee_paid_on' => '2026-01-10']);
    $litter->puppies()->create(['sex' => 'female', 'status' => 'home', 'sale_price' => 1500, 'went_home_on' => '2026-04-01']);
    $litter->puppies()->create(['sex' => 'male', 'status' => 'home', 'sale_price' => 1500, 'went_home_on' => '2026-04-01']);
    $litter->puppies()->create(['sex' => 'male', 'status' => 'available', 'sale_price' => 1500]);
    Expense::create(['spent_on' => '2026-03-01', 'category' => 'vet', 'amount_cents' => 25000, 'litter_id' => $litter->id]);
    Expense::create(['spent_on' => '2025-03-01', 'category' => 'vet', 'amount_cents' => 99900]);
    Litter::create(['sire_id' => $ours->id, 'status' => 'all_home', 'stud_fee' => 500, 'stud_fee_paid_on' => '2026-05-05']);

    $s = MoneySummary::forYear(2026);

    expect($s['income'])->toBe(3000_00 + 500_00)
        ->and($s['costs'])->toBe(250_00 + 800_00)
        ->and($s['net'])->toBe(3500_00 - 1050_00)
        ->and(MoneySummary::forYear(2025)['costs'])->toBe(999_00)
        ->and(collect($s['litters'])->firstWhere('id', $litter->id))->toMatchArray(['income' => 3000_00, 'costs' => 1050_00, 'puppies_sold' => 2]);
});

it('shows the page, lists years with data, and exports expenses', function () {
    Expense::create(['spent_on' => '2024-06-01', 'category' => 'food', 'amount_cents' => 1234]);

    $this->get(route('admin.money', ['year' => 2024]))->assertOk()->assertInertia(fn ($p) => $p
        ->component('admin/Money')->where('year', 2024)->has('expenses', 1)->where('summary.costs', 1234)
        ->where('years', fn ($y) => collect($y)->contains(2024) && collect($y)->contains(now()->year)));
    expect($this->get(route('admin.export', 'expenses'))->assertOk()->streamedContent())->toContain('12.34');
});
