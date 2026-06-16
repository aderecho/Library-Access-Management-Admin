<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')->orderBy('name')->get();
        $permissionGroups = config('permissions.groups');

        return view('admin.roles.index', compact('roles', 'permissionGroups'));
    }

    public function create()
    {
        $permissionGroups = config('permissions.groups');

        return view('admin.roles.create', compact('permissionGroups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:roles,slug'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($this->availablePermissions())],
        ]);

        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['name']);
        $validated['permissions'] = array_values($validated['permissions'] ?? []);

        Role::create($validated);

        return redirect()->route('admin.roles.index')->with('success', 'Role created.');
    }

    public function edit(Role $role)
    {
        $permissionGroups = config('permissions.groups');

        return view('admin.roles.edit', compact('role', 'permissionGroups'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role->id)],
            'slug' => ['required', 'string', 'max:100', Rule::unique('roles', 'slug')->ignore($role->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($this->availablePermissions())],
        ]);

        $validated['slug'] = Str::slug($validated['slug']);
        $validated['permissions'] = $role->slug === 'super-admin'
            ? $this->availablePermissions()
            : array_values($validated['permissions'] ?? []);

        $role->update($validated);

        return redirect()->route('admin.roles.index')->with('success', 'Role updated.');
    }

    public function updatePermissions(Request $request)
    {
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['nullable', 'array'],
            'permissions.*.*' => ['string', Rule::in($this->availablePermissions())],
        ]);

        Role::all()->each(function (Role $role) use ($validated) {
            $permissions = $role->slug === 'super-admin'
                ? $this->availablePermissions()
                : array_values($validated['permissions'][$role->id] ?? []);

            $role->update(['permissions' => $permissions]);
        });

        return redirect()->route('admin.roles.index')->with('success', 'Role permissions updated.');
    }

    private function availablePermissions(): array
    {
        return collect(config('permissions.groups'))->flatMap(fn (array $permissions) => array_keys($permissions))->values()->all();
    }
}
