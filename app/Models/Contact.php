<?php

namespace App\Models;

use App\Enums\ContactType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'type', 'email', 'phone', 'address', 'city', 'state', 'postal_code', 'notes'])]
class Contact extends Model
{
    protected function casts(): array
    {
        return ['type' => ContactType::class];
    }

    public function contactNotes(): HasMany
    {
        return $this->hasMany(ContactNote::class)->latest('noted_at');
    }

    public function waitlistEntries(): HasMany
    {
        return $this->hasMany(WaitlistEntry::class);
    }

    public function puppies(): HasMany
    {
        return $this->hasMany(Puppy::class);
    }
}
