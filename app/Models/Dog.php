<?php

namespace App\Models;

use App\Enums\CoatColor;
use App\Enums\DogRole;
use App\Enums\DogSex;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['name', 'registered_name', 'sex', 'role', 'color', 'birth_date', 'bio', 'is_public', 'registry_number', 'microchip', 'private_notes', 'sort_order'])]
class Dog extends Model
{
    use HasSlug;

    protected function casts(): array
    {
        return [
            'sex' => DogSex::class,
            'role' => DogRole::class,
            'color' => CoatColor::class,
            'birth_date' => 'date:Y-m-d',
            'is_public' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Photos are kept in the library when a dog is deleted.
        static::deleting(fn (Dog $dog) => $dog->photos()->update(['photoable_type' => null, 'photoable_id' => null]));
    }

    protected function slugSource(): string
    {
        return $this->name;
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
