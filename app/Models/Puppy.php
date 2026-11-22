<?php

namespace App\Models;

use App\Enums\CoatColor;
use App\Enums\DogSex;
use App\Enums\PuppyStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['name', 'collar', 'sex', 'color', 'birth_weight_g', 'status', 'contact_id', 'sale_price', 'notes'])]
class Puppy extends Model
{
    /** Editing a puppy refreshes the litter's "last updated" date. */
    protected $touches = ['litter'];

    protected function casts(): array
    {
        return ['sex' => DogSex::class, 'color' => CoatColor::class, 'status' => PuppyStatus::class];
    }

    public function litter(): BelongsTo
    {
        return $this->belongsTo(Litter::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function weights(): HasMany
    {
        return $this->hasMany(PuppyWeight::class)->orderBy('weighed_on');
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(Photo::class, 'photoable')->orderBy('sort_order')->orderBy('id');
    }
}
