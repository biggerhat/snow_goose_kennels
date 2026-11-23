<?php

namespace App\Models\Concerns;

use App\Models\Document;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** Private paperwork (PDFs, scans, photos of papers) attached to a record. */
trait HasDocuments
{
    public static function bootHasDocuments(): void
    {
        // Only a permanent delete removes the papers; archiving (soft delete) keeps them.
        static::deleting(function ($model) {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            $model->documents()->get()->each->delete();
        });
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable')->orderBy('category')->orderByDesc('issued_on')->orderByDesc('id');
    }
}
