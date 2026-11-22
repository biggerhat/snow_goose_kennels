<?php

namespace App\Models;

use App\Enums\BreedingMethod;
use App\Enums\LitterStatus;
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
use Illuminate\Support\Carbon;

#[Fillable(['name', 'sire_id', 'dam_id', 'status', 'bred_on', 'due_on', 'born_on', 'go_home_on', 'price', 'deposit', 'expected_count', 'description', 'is_public', 'private_notes', 'breeding_method', 'bred_place', 'bred_handler', 'stud_fee', 'stud_fee_paid_on', 'stillborn_count', 'akc_applied_on', 'akc_litter_number', 'sire_owner_signed_on', 'dam_owner_signed_on', 'multiple_sires', 'akc_marketplace_listed_on'])]
class Litter extends Model
{
    use HasDocuments;
    use HasSlug;
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => LitterStatus::class,
            'bred_on' => 'date:Y-m-d',
            'due_on' => 'date:Y-m-d',
            'born_on' => 'date:Y-m-d',
            'go_home_on' => 'date:Y-m-d',
            'stud_fee_paid_on' => 'date:Y-m-d',
            'akc_applied_on' => 'date:Y-m-d',
            'sire_owner_signed_on' => 'date:Y-m-d',
            'dam_owner_signed_on' => 'date:Y-m-d',
            'akc_marketplace_listed_on' => 'date:Y-m-d',
            'multiple_sires' => 'boolean',
            'breeding_method' => BreedingMethod::class,
            'is_public' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Photos of the litter and its puppies stay in the library when the litter is deleted.
        static::forceDeleting(function (Litter $litter) {
            $litter->photos()->update(['photoable_type' => null, 'photoable_id' => null]);
            HealthRecord::where('recordable_type', 'puppy')->whereIn('recordable_id', $litter->puppies()->select('id'))->delete();
            Document::where('documentable_type', 'puppy')->whereIn('documentable_id', $litter->puppies()->select('id'))->get()->each->delete();
            Photo::where('photoable_type', 'puppy')
                ->whereIn('photoable_id', $litter->puppies()->select('id'))
                ->update(['photoable_type' => null, 'photoable_id' => null]);
        });
    }

    protected function slugSource(): string
    {
        return $this->title;
    }

    protected function slugSourceAttribute(): string
    {
        return 'name';
    }

    public function sire(): BelongsTo
    {
        return $this->belongsTo(Dog::class, 'sire_id')->withTrashed();
    }

    public function dam(): BelongsTo
    {
        return $this->belongsTo(Dog::class, 'dam_id')->withTrashed();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(LitterTask::class)->orderBy('due_on')->orderBy('id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(LitterMessage::class)->latest('sent_at');
    }

    public function puppies(): HasMany
    {
        return $this->hasMany(Puppy::class)->orderBy('id');
    }

    public function diary(): HasMany
    {
        return $this->hasMany(DiaryEntry::class)->orderByDesc('posted_on')->orderByDesc('id');
    }

    public function waitlistEntries(): HasMany
    {
        return $this->hasMany(WaitlistEntry::class);
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(Photo::class, 'photoable')->orderBy('sort_order')->orderBy('id');
    }

    /** The date before which this litter's records must be kept (5 years after the last puppy went home). */
    public function purgeBlockedUntil(): ?CarbonInterface
    {
        if (! $this->born_on && ! $this->puppies()->exists()) {
            return null; // never happened: planned or expected only
        }

        $from = collect([$this->born_on, $this->puppies()->max('went_home_on'), $this->deleted_at ?? now()])
            ->filter()->map(fn ($d) => Carbon::parse($d))->max();
        $until = $from->copy()->addYears(5);

        return $until->isFuture() ? $until : null;
    }

    /** Public headline: the chosen name, or "Dam × Sire". */
    protected function title(): Attribute
    {
        return Attribute::get(function () {
            if ($this->name) {
                return $this->name;
            }
            $parts = array_filter([$this->dam?->name, $this->sire?->name]);

            return $parts ? implode(' × ', $parts) : 'Litter';
        });
    }

    /** Best date to show as "when": born, else due, else bred. */
    protected function keyDate(): Attribute
    {
        return Attribute::get(fn () => $this->born_on ?? $this->due_on ?? $this->bred_on);
    }
}
