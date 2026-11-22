<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A private folder for organising photos. Albums never appear on the public site. */
#[Fillable(['name', 'description', 'sort_order'])]
class Album extends Model
{
    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class)->orderByDesc('id');
    }
}
