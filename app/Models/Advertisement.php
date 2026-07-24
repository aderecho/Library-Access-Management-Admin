<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    protected $fillable = [
        'created_by',
        'title',
        'description',
        'image_path',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function displayStatus(): string
    {
        if ($this->starts_at?->isFuture()) {
            return 'Scheduled';
        }

        if ($this->ends_at?->isPast()) {
            return 'Ended';
        }

        return 'Active';
    }
}
