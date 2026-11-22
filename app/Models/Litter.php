<?php

namespace App\Models;

use App\Enums\LitterStatus;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['name', 'sire_id', 'dam_id', 'status', 'bred_on', 'due_on', 'born_on', 'go_home_on', 'price', 'deposit', 'expected_count', 'description', 'is_public', 'private_notes'])]
class Litter extends Model
{
    use HasSlug;

    protected function casts(): array
    {
        return [
            'status' => LitterStatus::class,
            'bred_on' => 'date:Y-m-d',
            'due_on' => 'date:Y-m-d',
            'born_on' => 'date:Y-m-d',
            'go_home_on' => 'date:Y-m-d',
            'is_public' => 'boolean',
        ];
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
        return $this->belongsTo(Dog::class, 'sire_id');
    }

    public function dam(): BelongsTo
    {
        return $this->belongsTo(Dog::class, 'dam_id');
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
