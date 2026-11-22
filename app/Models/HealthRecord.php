<?php

namespace App\Models;

use App\Enums\HealthRecordType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** A vaccine, deworming, vet visit or medication for a dog or a puppy (not the OFA-style clearance tests). */
#[Fillable(['type', 'name', 'given_on', 'next_due_on', 'given_by', 'lot_number', 'notes'])]
class HealthRecord extends Model
{
    protected function casts(): array
    {
        return [
            'type' => HealthRecordType::class,
            'given_on' => 'date:Y-m-d',
            'next_due_on' => 'date:Y-m-d',
        ];
    }

    public function recordable(): MorphTo
    {
        return $this->morphTo();
    }
}
