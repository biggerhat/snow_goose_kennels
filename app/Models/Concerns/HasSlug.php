<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasSlug
{
    /** The attribute the slug is derived from. */
    abstract protected function slugSource(): string;

    protected static function bootHasSlug(): void
    {
        static::saving(function ($model) {
            if ($model->slug && ! $model->isDirty($model->slugSourceAttribute())) {
                return;
            }

            $base = Str::slug($model->slugSource()) ?: 'item';
            $slug = $base;
            $i = 2;

            while (static::where('slug', $slug)->whereKeyNot($model->getKey() ?? 0)->exists()) {
                $slug = $base.'-'.$i++;
            }

            $model->slug = $slug;
        });
    }

    protected function slugSourceAttribute(): string
    {
        return 'name';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
