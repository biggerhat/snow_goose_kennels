<?php

namespace App\Models;

use App\Enums\CoatColor;
use App\Enums\DogSex;
use App\Enums\PuppyStatus;
use App\Enums\RegistrationType;
use App\Models\Concerns\HasDocuments;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['name', 'collar', 'sex', 'color', 'birth_weight_g', 'status', 'contact_id', 'sale_price', 'notes', 'microchip', 'registration_type', 'akc_number', 'papers_given_on', 'went_home_on', 'vet_checked_on', 'reunite_enrolled_on', 'born_time', 'birth_order', 'birth_notes'])]
class Puppy extends Model
{
    use HasDocuments;

    /** Editing a puppy refreshes the litter's "last updated" date. */
    protected $touches = ['litter'];

    protected function casts(): array
    {
        return [
            'sex' => DogSex::class,
            'color' => CoatColor::class,
            'status' => PuppyStatus::class,
            'registration_type' => RegistrationType::class,
            'papers_given_on' => 'date:Y-m-d',
            'went_home_on' => 'date:Y-m-d',
            'vet_checked_on' => 'date:Y-m-d',
            'reunite_enrolled_on' => 'date:Y-m-d',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Puppy $puppy) {
            $puppy->photos()->update(['photoable_type' => null, 'photoable_id' => null]);
            $puppy->healthRecords()->delete();
        });
    }

    public function healthRecords(): MorphMany
    {
        return $this->morphMany(HealthRecord::class, 'recordable')->orderByDesc('given_on')->orderByDesc('id');
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
