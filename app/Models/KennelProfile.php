<?php

namespace App\Models;

use App\Models\Concerns\HasDocuments;
use Illuminate\Database\Eloquent\Model;

/** One row that stands for the kennel itself, so kennel-wide papers (emergency plan, inspection reports) have a home. */
class KennelProfile extends Model
{
    use HasDocuments;

    public static function instance(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
