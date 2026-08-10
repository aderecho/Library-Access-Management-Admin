<?php

namespace App\Models;

use App\Models\Concerns\HasPersonName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

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
        return $this->isSuperAdmin() || (int) $this->branch_id === $branchId;
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

    public function roles()
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function assignedRoles(): Collection
    {
        $roles = collect($this->roles->all());

        if ($this->role && ! $roles->contains('id', $this->role->id)) {
            $roles = $roles->prepend($this->role);
        }

        return $roles->sortBy('name')->values();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasAnyRole(['super-admin']);
    }

    public function hasAnyRole(array $slugs): bool
    {
        return $this->is_active
            && $this->assignedRoles()->contains(fn (Role $role) => in_array($role->slug, $slugs, true));
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return $this->assignedRoles()->contains(
            fn (Role $role) => $role->slug === 'super-admin'
                || in_array($permission, $role->permissions ?? [], true)
        );
    }
}
