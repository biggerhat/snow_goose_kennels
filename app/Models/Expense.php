<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['spent_on', 'category', 'amount_cents', 'vendor', 'description', 'litter_id', 'dog_id'])]
class Expense extends Model
{
    protected function casts(): array
    {
        return ['spent_on' => 'date:Y-m-d', 'category' => ExpenseCategory::class, 'amount_cents' => 'integer'];
    }

    public function litter(): BelongsTo
    {
        return $this->belongsTo(Litter::class)->withTrashed();
    }

    public function dog(): BelongsTo
    {
        return $this->belongsTo(Dog::class)->withTrashed();
    }
}
