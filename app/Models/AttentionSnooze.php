<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** "Remind me about this later": hides one dashboard item for a few days. */
#[Fillable(['key', 'until'])]
class AttentionSnooze extends Model
{
    protected function casts(): array
    {
        return ['until' => 'date'];
    }
}
