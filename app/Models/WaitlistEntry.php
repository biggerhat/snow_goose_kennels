<?php

namespace App\Models;

use App\Enums\CoatColor;
use App\Enums\DogSex;
use App\Enums\WaitlistStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['contact_id', 'litter_id', 'puppy_id', 'preferred_sex', 'preferred_color', 'pick_order', 'status', 'deposit_amount', 'deposit_paid_on', 'notes', 'source', 'application'])]
class WaitlistEntry extends Model
{
    protected function casts(): array
    {
        return [
            'preferred_sex' => DogSex::class,
            'preferred_color' => CoatColor::class,
            'status' => WaitlistStatus::class,
            'deposit_paid_on' => 'date:Y-m-d',
            'application' => 'array',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function litter(): BelongsTo
    {
        return $this->belongsTo(Litter::class);
    }

    public function puppy(): BelongsTo
    {
        return $this->belongsTo(Puppy::class);
    }
}
