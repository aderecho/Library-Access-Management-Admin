<section class="panel form-panel">
    <form method="post" action="{{ $action }}" class="form-grid">
        @csrf
        @if($method === 'put') @method('put') @endif

        <label>Role name
            <input name="name" value="{{ old('name', $role?->name) }}" required>
        </label>

        <label>Slug
            <input name="slug" value="{{ old('slug', $role?->slug) }}" placeholder="Generated automatically for new roles">
        </label>

        <label>Description
            <textarea name="description" rows="4">{{ old('description', $role?->description) }}</textarea>
        </label>

        <fieldset class="role-permissions">
            <legend>Page and action access</legend>
            <p class="muted">Select any combination of permissions for this role. For example, you can allow both Entry Monitor and Reports without granting Dashboard or Transactions access.</p>

            @foreach($permissionGroups as $group => $permissions)
                <div class="role-permission-group">
                    <strong>{{ $group }}</strong>
                    <div class="role-permission-options">
                        @foreach($permissions as $permission => $label)
                            <label class="permission-option">
                                <input
                                    type="checkbox"
                                    name="permissions[]"
                                    value="{{ $permission }}"
                                    @checked($role?->slug === 'super-admin' || in_array($permission, old('permissions', $role?->permissions ?? []), true))
                                    @disabled($role?->slug === 'super-admin')
                                >
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </fieldset>

        <div class="form-actions">
            <a class="button secondary" href="{{ route('admin.roles.index') }}">Cancel</a>
            <button class="primary" type="submit">Save role</button>
        </div>
    </form>
</section>
