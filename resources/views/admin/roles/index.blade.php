@extends('layouts.admin', ['heading' => 'User Roles'])

@section('content')
<section class="panel">
    <div class="panel-heading">
        <div>
            <span class="eyebrow">Administration</span>
            <h2>User Role Manager</h2>
            <p class="muted">Assign granular access permissions to each user role.</p>
        </div>
        @if(auth()->user()->hasPermission('roles.create'))
            <a class="button primary" href="{{ route('admin.roles.create') }}">Add new role</a>
        @endif
    </div>

    <form method="post" action="{{ route('admin.roles.permissions.update') }}">
        @csrf
        @method('patch')

        <div class="permission-matrix-wrap">
            <table class="permission-matrix">
                <thead>
                    <tr>
                        <th>Actions</th>
                        @foreach($roles as $role)
                            <th>
                                <strong>{{ $role->name }}</strong>
                                <span>{{ $role->users_count }} {{ Str::plural('user', $role->users_count) }}</span>
                                @if(auth()->user()->hasPermission('roles.update'))
                                    <a href="{{ route('admin.roles.edit', $role) }}">Edit role</a>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($permissionGroups as $group => $permissions)
                        <tr class="permission-group-row">
                            <th colspan="{{ $roles->count() + 1 }}">{{ $group }}</th>
                        </tr>
                        @foreach($permissions as $permission => $label)
                            <tr>
                                <td>{{ $label }}</td>
                                @foreach($roles as $role)
                                    <td>
                                        <input
                                            type="checkbox"
                                            name="permissions[{{ $role->id }}][]"
                                            value="{{ $permission }}"
                                            aria-label="{{ $role->name }}: {{ $label }}"
                                            @checked($role->slug === 'super-admin' || in_array($permission, $role->permissions ?? [], true))
                                            @disabled($role->slug === 'super-admin' || ! auth()->user()->hasPermission('roles.update'))
                                        >
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(auth()->user()->hasPermission('roles.update'))
            <div class="form-actions permission-actions">
                <button class="primary" type="submit">Save permissions</button>
            </div>
        @endif
    </form>
</section>
@endsection
