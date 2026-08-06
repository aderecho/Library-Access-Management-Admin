<?php

namespace App\Models;

use App\Models\Concerns\HasPersonName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Student extends Model
{
    use HasPersonName;

    protected $fillable = [
        'campus_id',
        'rfid_code',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
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

    public function photos(): MorphMany
    {
        return $this->morphMany(CardholderPhoto::class, 'photoable');
    }

    public function primaryPhoto()
    {
        return $this->morphOne(CardholderPhoto::class, 'photoable')->where('is_primary', true);
    }
}
