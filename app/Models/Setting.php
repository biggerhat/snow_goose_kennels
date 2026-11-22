<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /** @var array<string, string|null>|null */
    private static ?array $cache = null;

    protected static function booted(): void
    {
        static::saved(fn () => self::$cache = null);
        static::deleted(fn () => self::$cache = null);
    }

    /** @return array<string, string|null> */
    public static function values(): array
    {
        return self::$cache ??= static::query()->pluck('value', 'key')->all();
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = self::values()[$key] ?? null;

        return $value === null || $value === '' ? $default : $value;
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /** Contact details: edited in the admin, falling back to .env. */
    public static function contact(): array
    {
        return [
            'phone' => self::get('phone', config('kennel.phone')),
            'email' => self::get('email', config('kennel.email')),
            'location' => self::get('location', config('kennel.location')),
            'facebook' => self::get('facebook', config('kennel.facebook')),
        ];
    }
}
