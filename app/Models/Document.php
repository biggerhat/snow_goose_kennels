<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * A private file. Stored on the non-public disk and only ever served to a logged-in owner.
 */
#[Fillable(['documentable_type', 'documentable_id', 'category', 'title', 'path', 'original_name', 'mime', 'size', 'issued_on', 'expires_on', 'notes'])]
class Document extends Model
{
    protected $hidden = ['path'];

    protected $appends = ['url'];

    protected static function booted(): void
    {
        static::deleted(fn (Document $d) => Storage::disk(config('kennel.document_disk'))->delete($d->path));
    }

    protected function casts(): array
    {
        return ['issued_on' => 'date:Y-m-d', 'expires_on' => 'date:Y-m-d'];
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): string
    {
        return route('admin.documents.show', $this);
    }
}
