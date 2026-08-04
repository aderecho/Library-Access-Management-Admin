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
        'media_type',
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
            return 'Expired';
        }

        return 'Published';
    }

    public function scopePublished($query)
    {
        return $query
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function scopeScheduled($query)
    {
        return $query->where('starts_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('ends_at')->where('ends_at', '<', now());
    }
}
