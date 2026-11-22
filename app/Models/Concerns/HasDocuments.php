<?php

namespace App\Models\Concerns;

use App\Models\Document;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** Private paperwork (PDFs, scans, photos of papers) attached to a record. */
trait HasDocuments
{
    public static function bootHasDocuments(): void
    {
        static::deleting(function ($model) {
            $model->documents()->get()->each->delete();
        });
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable')->orderBy('category')->orderByDesc('issued_on')->orderByDesc('id');
    }
}
