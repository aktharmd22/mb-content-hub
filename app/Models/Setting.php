<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'is_encrypted'];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    private const CACHE_KEY = 'app.settings.all';

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever(self::CACHE_KEY, fn () => self::loadAll());

        return $all[$key] ?? $default;
    }

    public static function put(string $key, mixed $value, bool $encrypted = false): void
    {
        $stored = $value;
        if ($encrypted && $value !== null) {
            $stored = Crypt::encryptString((string) $value);
        }

        self::updateOrCreate(
            ['key' => $key],
            ['value' => $stored, 'is_encrypted' => $encrypted],
        );

        self::flushCache();
    }

    public static function forget(string $key): void
    {
        self::where('key', $key)->delete();
        self::flushCache();
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private static function loadAll(): array
    {
        return self::all()->mapWithKeys(function (self $row) {
            $value = $row->value;
            if ($row->is_encrypted && $value !== null) {
                try {
                    $value = Crypt::decryptString($value);
                } catch (\Throwable) {
                    $value = null;
                }
            }
            return [$row->key => $value];
        })->all();
    }
}
