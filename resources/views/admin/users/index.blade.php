@extends('layouts.admin', ['heading' => 'User Accounts'])

@section('content')
<section class="panel">
    <div class="panel-heading">
        <div><span class="eyebrow">Administration</span><h2>Manage User Accounts</h2></div>
        @if(auth()->user()->hasPermission('users.create'))
            <a class="button primary" href="{{ route('admin.users.create') }}">Add new user</a>
        @endif
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>First Name</th><th>Middle Name</th><th>Last Name</th><th>Suffix</th><th>Email</th><th>Roles</th><th>Assigned Branch</th><th>Status</th><th>Created</th><th></th></tr></thead>
            <tbody>
            @foreach($users as $user)
                <tr>
                    <td>{{ $user->first_name }}</td>
                    <td>{{ $user->middle_name ?: '—' }}</td>
                    <td>{{ $user->last_name }}</td>
                    <td>{{ $user->suffix ?: '—' }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->assignedRoles()->pluck('name')->join(', ') ?: '—' }}</td>
                    <td><strong>{{ $user->isSuperAdmin() ? 'All branches' : ($user->branch?->name ?? 'Unassigned') }}</strong></td>
                    <td><span class="badge {{ $user->is_active ? 'valid' : 'invalid' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td>{{ $user->created_at?->format('Y-m-d') }}</td>
                    <td>
                        @if(auth()->user()->hasPermission('users.update'))
                            <a href="{{ route('admin.users.edit', $user) }}">Edit</a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
</section>
@endsection
