<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['puppy_id', 'weighed_on', 'weight_g'])]
class PuppyWeight extends Model
{
    protected $touches = [];

    protected function casts(): array
    {
        return ['weighed_on' => 'date:Y-m-d'];
    }

    public function puppy(): BelongsTo
    {
        return $this->belongsTo(Puppy::class);
    }
}
