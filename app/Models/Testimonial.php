<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'location', 'puppy_name', 'email', 'body', 'is_public', 'needs_review', 'sort_order'])]
class Testimonial extends Model
{
    protected function casts(): array
    {
        return ['is_public' => 'boolean', 'needs_review' => 'boolean'];
    }
}
