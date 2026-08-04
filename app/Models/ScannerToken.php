<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ScannerToken extends Model
{
    protected $fillable = [
        'branch_id',
        'name',
        'device_id',
        'token_hash',
        'token_prefix',
        'is_active',
        'last_used_at',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public static function generateToken(): string
    {
        return 'upcebu_scanner_'.Str::random(48);
    }

    public function replaceToken(string $token): void
    {
        $this->forceFill([
            'token_hash' => hash('sha256', $token),
            'token_prefix' => substr($token, 0, 22),
        ])->save();
    }
}
