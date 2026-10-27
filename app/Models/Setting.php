<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Key/value store for the defaults in PRD §6.13 (cutoff, payment window, upload limits, ...).
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    public static function valueOf(string $key, ?string $default = null): ?string
    {
        return static::query()->find($key)?->value ?? $default;
    }
}
