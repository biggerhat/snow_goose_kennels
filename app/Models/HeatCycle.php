<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['started_on', 'ended_on', 'bred_on', 'notes'])]
class HeatCycle extends Model
{
    protected function casts(): array
    {
        return ['started_on' => 'date:Y-m-d', 'ended_on' => 'date:Y-m-d', 'bred_on' => 'date:Y-m-d'];
    }

    public function dog(): BelongsTo
    {
        return $this->belongsTo(Dog::class);
    }
}
