<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CardholderPhoto extends Model
{
    protected $fillable = ['path', 'is_primary'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function photoable(): MorphTo
    {
        return $this->morphTo();
    }
}
