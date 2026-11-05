<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameplaySetting extends Model
{
    protected $guarded = [];

    public static function valueOf(string $key): mixed
    {
        $default = config("gameplay.defaults.{$key}");
        $value = static::query()->where('key', $key)->value('value');
        if ($value === null) return $default;
        if (is_bool($default)) return filter_var($value, FILTER_VALIDATE_BOOL);
        if (is_int($default)) return (int) $value;
        return $value;
    }

    public static function snapshot(): array
    {
        return collect(config('gameplay.defaults'))->mapWithKeys(fn ($value, $key) => [$key => static::valueOf($key)])->all();
    }
}
