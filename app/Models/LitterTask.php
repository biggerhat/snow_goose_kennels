<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One step in the puppy schedule: a deworming day, a weigh-in, a vet visit. */
#[Fillable(['kind', 'title', 'detail', 'due_on', 'done_on'])]
class LitterTask extends Model
{
    protected function casts(): array
    {
        return ['due_on' => 'date:Y-m-d', 'done_on' => 'date:Y-m-d'];
    }

    public function litter(): BelongsTo
    {
        return $this->belongsTo(Litter::class);
    }
}
