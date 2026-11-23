<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A title or award a dog has earned, such as MH from a hunt test. */
#[Fillable(['title', 'full_name', 'kind', 'earned_on', 'event', 'notes'])]
class DogAchievement extends Model
{
    protected function casts(): array
    {
        return ['earned_on' => 'date:Y-m-d'];
    }

    public function dog(): BelongsTo
    {
        return $this->belongsTo(Dog::class);
    }
}
