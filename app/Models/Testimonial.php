<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'location', 'puppy_name', 'body', 'is_public', 'sort_order'])]
class Testimonial extends Model
{
    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }
}
