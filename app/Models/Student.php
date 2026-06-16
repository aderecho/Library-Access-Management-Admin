<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'campus_id',
        'rfid_code',
        'name',
        'program',
        'college',
        'year_level',
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
}
