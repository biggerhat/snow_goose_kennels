<?php

namespace App\Models;

use App\Enums\CoatColor;
use App\Enums\DogLeftReason;
use App\Enums\DogRole;
use App\Enums\DogSex;
use App\Models\Concerns\HasDocuments;
use App\Models\Concerns\HasSlug;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'registered_name', 'sex', 'role', 'color', 'birth_date', 'bio', 'is_public', 'registry_number', 'microchip', 'private_notes', 'sort_order', 'sire_id', 'dam_id', 'is_ours', 'owner_contact_id', 'titles', 'dna_profile', 'acquired_on', 'left_on', 'left_reason', 'co_owner_name'])]
class Dog extends Model
{
    use HasDocuments;
    use HasSlug;
    use SoftDeletes;

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
        static::forceDeleting(function (Dog $dog) {
            $dog->photos()->update(['photoable_type' => null, 'photoable_id' => null]);
            $dog->healthRecords()->delete();
            $dog->achievements()->delete();
        });
    }

    protected function slugSource(): string
    {
        return $this->name;
    }

    public function sire(): BelongsTo
    {
        return $this->belongsTo(self::class, 'sire_id')->withTrashed();
    }

    public function dam(): BelongsTo
    {
        return $this->belongsTo(self::class, 'dam_id')->withTrashed();
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

    public function achievements(): HasMany
    {
        return $this->hasMany(DogAchievement::class)->orderByDesc('earned_on')->orderByDesc('id');
    }

    public function healthRecords(): MorphMany
    {
        return $this->morphMany(HealthRecord::class, 'recordable')->orderByDesc('given_on')->orderByDesc('id');
    }

    public function heatCycles(): HasMany
    {
        return $this->hasMany(HeatCycle::class)->orderByDesc('started_on');
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(Photo::class, 'photoable')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * AKC asks for ownership and breeding records to be kept for 5 years after a dog dies, is sold or is
     * given away. Returns the date before which this dog must not be permanently deleted, if any.
     * A dog added by mistake (new, never bred, never left) can be deleted straight away.
     */
    public function purgeBlockedUntil(): ?CarbonInterface
    {
        $everBred = Litter::withTrashed()->where(fn ($q) => $q->where('sire_id', $this->id)->orWhere('dam_id', $this->id))->exists();

        if (! $this->left_on && ! $everBred && $this->created_at?->gt(now()->subDays(14))) {
            return null;
        }

        $from = collect([$this->left_on, $this->deleted_at ?? now()])->filter()->max();
        $until = $from->copy()->addYears(5);

        return $until->isFuture() ? $until : null;
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
