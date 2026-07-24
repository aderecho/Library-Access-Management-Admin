<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Employee extends Model
{
    protected $fillable = [
        'employee_number',
        'rfid_code',
        'name',
        'position',
        'office',
        'status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function transactions()
    {
        return $this->hasMany(RfidTransaction::class);
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(CardholderPhoto::class, 'photoable');
    }

    public function primaryPhoto()
    {
        return $this->morphOne(CardholderPhoto::class, 'photoable')->where('is_primary', true);
    }
}
