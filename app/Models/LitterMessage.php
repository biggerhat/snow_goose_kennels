<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A news email sent to the people waiting for a litter, kept so she can see what she told them. */
#[Fillable(['subject', 'body', 'recipient_count', 'sent_at'])]
class LitterMessage extends Model
{
    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function litter(): BelongsTo
    {
        return $this->belongsTo(Litter::class);
    }
}
