<section class="panel form-panel">
    <form method="post" action="{{ $action }}" class="form-grid">
        @csrf
        @if($method === 'put') @method('put') @endif

        <label>Name
            <input name="name" value="{{ old('name', $user?->name) }}" required>
        </label>

        <label>Email
            <input type="email" name="email" value="{{ old('email', $user?->email) }}" required>
        </label>

        <fieldset class="user-role-selector">
            <legend>Assign user role</legend>
            <p class="muted">Select one role. The assigned permissions are shown below each role.</p>

            <div class="role-card-grid">
                @foreach($roles as $role)
                    @php
                        $rolePermissions = $role->slug === 'super-admin'
                            ? collect($permissionGroups)->flatMap(fn ($permissions) => array_keys($permissions))->all()
                            : ($role->permissions ?? []);
                    @endphp
                    <label class="role-card">
                        <input
                            type="radio"
                            name="role_id"
                            value="{{ $role->id }}"
                            @checked((string) old('role_id', $user?->role_id) === (string) $role->id)
                            required
                        >
                        <span class="role-card-content">
                            <span class="role-card-header">
                                <strong>{{ $role->name }}</strong>
                                <small>{{ count($rolePermissions) }} permissions</small>
                            </span>
                            <span class="role-card-description">{{ $role->description ?: 'No description provided.' }}</span>

                            <span class="role-card-permissions">
                                @foreach($permissionGroups as $group => $permissions)
                                    @php($allowed = collect($permissions)->only($rolePermissions))
                                    @if($allowed->isNotEmpty())
                                        <span class="role-card-group">
                                            <b>{{ $group }}</b>
                                            <span>{{ $allowed->count() }} allowed</span>
                                        </span>
                                    @endif
                                @endforeach
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <label>Password {{ $user ? '(leave blank to retain current password)' : '' }}
            <input type="password" name="password" {{ $user ? '' : 'required' }}>
        </label>

        <label>Confirm password
            <input type="password" name="password_confirmation" {{ $user ? '' : 'required' }}>
        </label>

        <label class="checkbox">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user?->is_active ?? true))>
            <span>Active account</span>
        </label>

        <div class="form-actions">
            <a class="button secondary" href="{{ route('admin.users.index') }}">Cancel</a>
            <button class="primary" type="submit">Save user</button>
        </div>
    </form>
</section>
