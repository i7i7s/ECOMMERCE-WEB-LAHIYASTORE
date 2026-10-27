<?php

namespace App\Models;

use Database\Factories\CaseColorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'hex_code', 'is_active', 'sort_order'])]
class CaseColor extends Model
{
    /** @use HasFactory<CaseColorFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
