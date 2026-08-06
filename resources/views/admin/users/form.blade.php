<section class="panel form-panel">
    <form method="post" action="{{ $action }}" class="form-grid">
        @csrf
        @if($method === 'put') @method('put') @endif

        <div class="person-name-grid">
            <label>First name
                <input name="first_name" value="{{ old('first_name', $user?->first_name) }}" maxlength="80" required>
                @error('first_name')<span class="field-error">{{ $message }}</span>@enderror
            </label>

            <label>Middle name
                <input name="middle_name" value="{{ old('middle_name', $user?->middle_name) }}" maxlength="80">
                @error('middle_name')<span class="field-error">{{ $message }}</span>@enderror
            </label>

            <label>Last name
                <input name="last_name" value="{{ old('last_name', $user?->last_name) }}" maxlength="80" required>
                @error('last_name')<span class="field-error">{{ $message }}</span>@enderror
            </label>

            <label>Suffix
                <input name="suffix" value="{{ old('suffix', $user?->suffix) }}" maxlength="30" placeholder="Jr., Sr., III">
                @error('suffix')<span class="field-error">{{ $message }}</span>@enderror
            </label>
        </div>

        <label>Email
            <input type="email" name="email" value="{{ old('email', $user?->email) }}" required>
        </label>

        <label>Assigned branch
            <select name="branch_id">
                <option value="">All branches (super-admin only)</option>
                @foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((string) old('branch_id', $user?->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>@endforeach
            </select>
            <small class="muted">Required for every non-super-admin user. Their dashboard, reports, logs, and entry monitor are limited to this branch.</small>
            @error('branch_id')<span class="field-error">{{ $message }}</span>@enderror
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
