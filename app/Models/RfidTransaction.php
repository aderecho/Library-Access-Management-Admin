<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class RfidTransaction extends Model
{
    private const CACHE_VERSION_KEY = 'rfid-transactions:version';

    protected $fillable = [
        'branch_id',
        'student_id',
        'employee_id',
        'cardholder_type',
        'rfid_code',
        'campus_id',
        'cardholder_name',
        'program',
        'college_department',
        'year_level',
        'transaction_type',
        'status',
        'message',
        'scanned_at',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::invalidateCachedAnalytics());
        static::deleted(fn () => static::invalidateCachedAnalytics());
    }

    public static function cacheVersion(): int
    {
        return (int) Cache::rememberForever(self::CACHE_VERSION_KEY, fn () => 1);
    }

    public static function invalidateCachedAnalytics(): void
    {
        if (Cache::increment(self::CACHE_VERSION_KEY) === false) {
            Cache::forever(self::CACHE_VERSION_KEY, 2);
        }
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function cardholderPhotoPath(): ?string
    {
        return $this->student?->primaryPhoto?->path
            ?? $this->employee?->primaryPhoto?->path;
    }
}
