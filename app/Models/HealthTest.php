<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'result', 'tested_on', 'certificate_number', 'verification_url', 'is_public', 'notes'])]
class HealthTest extends Model
{
    protected function casts(): array
    {
        return ['tested_on' => 'date:Y-m-d', 'is_public' => 'boolean'];
    }

    public function dog(): BelongsTo
    {
        return $this->belongsTo(Dog::class);
    }
}
