<?php

namespace App\Models;

use App\Enums\CoatColor;
use App\Enums\DogLeftReason;
use App\Enums\DogRole;
use App\Enums\DogSex;
use App\Models\Concerns\HasDocuments;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['name', 'registered_name', 'sex', 'role', 'color', 'birth_date', 'bio', 'is_public', 'registry_number', 'microchip', 'private_notes', 'sort_order', 'sire_id', 'dam_id', 'is_ours', 'owner_contact_id', 'titles', 'dna_profile', 'acquired_on', 'left_on', 'left_reason'])]
class Dog extends Model
{
    use HasDocuments;
    use HasSlug;

    protected $attributes = ['is_ours' => true];

    protected function casts(): array
    {
        return [
            'sex' => DogSex::class,
            'role' => DogRole::class,
            'color' => CoatColor::class,
            'birth_date' => 'date:Y-m-d',
            'acquired_on' => 'date:Y-m-d',
            'left_on' => 'date:Y-m-d',
            'left_reason' => DogLeftReason::class,
            'is_public' => 'boolean',
            'is_ours' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // A dog from another kennel is never shown on the public Our Dogs pages.
        static::saving(function (Dog $dog) {
            if ($dog->is_ours === false) {
                $dog->is_public = false;
            }
        });

        // Photos are kept in the library when a dog is deleted.
        static::deleting(fn (Dog $dog) => $dog->photos()->update(['photoable_type' => null, 'photoable_id' => null]));
    }

    protected function slugSource(): string
    {
        return $this->name;
    }

    public function sire(): BelongsTo
    {
        return $this->belongsTo(self::class, 'sire_id');
    }

    public function dam(): BelongsTo
    {
        return $this->belongsTo(self::class, 'dam_id');
    }

    /** Who owns a dog that lives at another kennel. */
    public function ownerContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'owner_contact_id');
    }

    public function healthTests(): HasMany
    {
        return $this->hasMany(HealthTest::class)->orderBy('name');
    }

    public function heatCycles(): HasMany
    {
        return $this->hasMany(HeatCycle::class)->orderByDesc('started_on');
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(Photo::class, 'photoable')->orderBy('sort_order')->orderBy('id');
    }

    protected function ageLabel(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->birth_date) {
                return null;
            }
            $years = (int) $this->birth_date->diffInYears(now());

            return $years < 1 ? (int) $this->birth_date->diffInMonths(now()).' mo' : $years.' yr';
        });
    }
}
