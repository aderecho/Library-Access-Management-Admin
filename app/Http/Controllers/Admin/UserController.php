<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['roles', 'role', 'branch'])->latest()->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();
        $permissionGroups = config('permissions.groups');
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('admin.users.create', compact('roles', 'permissionGroups', 'branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'suffix' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'distinct', 'exists:roles,id'],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('is_active', true)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $roles = Role::whereKey($validated['role_ids'])->get();
        $roleIds = $roles->pluck('id')->all();
        unset($validated['role_ids']);
        $validated = $this->enforceBranchAssignment($validated, $roles);
        $validated['role_id'] = $this->primaryRole($roles)->id;
        $validated['password'] = Str::random(64);
        $validated['is_active'] = $request->boolean('is_active');

        DB::transaction(function () use ($validated, $roleIds): void {
            $user = User::create($validated);
            $user->roles()->sync($roleIds);
        });

        return redirect()->route('admin.users.index')->with('success', 'User account created.');
    }

    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        $permissionGroups = config('permissions.groups');
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'roles', 'permissionGroups', 'branches'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'suffix' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'distinct', 'exists:roles,id'],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('is_active', true)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $roles = Role::whereKey($validated['role_ids'])->get();
        $roleIds = $roles->pluck('id')->all();
        unset($validated['role_ids']);
        $validated = $this->enforceBranchAssignment($validated, $roles);
        $validated['role_id'] = $this->primaryRole($roles)->id;
        $validated['is_active'] = $request->boolean('is_active');

        DB::transaction(function () use ($user, $validated, $roleIds): void {
            $user->update($validated);
            $user->roles()->sync($roleIds);
        });

        return redirect()->route('admin.users.index')->with('success', 'User account updated.');
    }

    private function enforceBranchAssignment(array $validated, Collection $roles): array
    {
        if (! $roles->contains('slug', 'super-admin') && empty($validated['branch_id'])) {
            throw ValidationException::withMessages([
                'branch_id' => 'Select the branch this user is assigned to.',
            ]);
        }

        if ($roles->contains('slug', 'super-admin')) {
            $validated['branch_id'] = null;
        }

        return $validated;
    }

    private function primaryRole(Collection $roles): Role
    {
        return $roles->firstWhere('slug', 'super-admin') ?? $roles->first();
    }
}
