<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['posted_on', 'title', 'body', 'is_public'])]
class DiaryEntry extends Model
{
    /** A new diary entry refreshes the litter's "last updated" date. */
    protected $touches = ['litter'];

    protected function casts(): array
    {
        return ['posted_on' => 'date:Y-m-d', 'is_public' => 'boolean'];
    }

    public function litter(): BelongsTo
    {
        return $this->belongsTo(Litter::class);
    }
}
