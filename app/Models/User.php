<?php

namespace App\Models;

use App\Models\Concerns\HasPersonName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, HasPersonName, Notifiable;

    protected $fillable = [
        'branch_id',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'name',
        'email',
        'password',
        'role_id',
        'is_active',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function canAccessBranch(int $branchId): bool
    {
        return $this->role?->slug === 'super-admin' || (int) $this->branch_id === $branchId;
    }

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
