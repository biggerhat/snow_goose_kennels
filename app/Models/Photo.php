<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['photoable_type', 'photoable_id', 'path', 'thumb_path', 'caption', 'album_id', 'taken_on', 'credit', 'is_public', 'in_gallery', 'sort_order'])]
class Photo extends Model
{
    protected $appends = ['url', 'thumb_url'];

    protected function casts(): array
    {
        return ['is_public' => 'boolean', 'in_gallery' => 'boolean', 'taken_on' => 'date:Y-m-d'];
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    public function photoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk(config('kennel.photo_disk'))->url($this->path);
    }

    public function getThumbUrlAttribute(): string
    {
        return Storage::disk(config('kennel.photo_disk'))->url($this->thumb_path);
    }
}
