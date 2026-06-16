<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasAnyRole(array $slugs): bool
    {
        return $this->is_active && $this->role && in_array($this->role->slug, $slugs, true);
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->is_active || ! $this->role) {
            return false;
        }

        return $this->role->slug === 'super-admin'
            || in_array($permission, $this->role->permissions ?? [], true);
    }
}
