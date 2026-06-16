@extends('layouts.admin', ['heading' => 'Scanner Registrations'])

@section('content')
@if(session('generated_scanner_token'))
    <section class="panel scanner-token-reveal" aria-labelledby="generated-token-heading">
        <div>
            <span class="eyebrow">New scanner credential</span>
            <h2 id="generated-token-heading">Copy this token now</h2>
            <p class="muted">This permanent installation token is shown only once. Regenerate it to revoke the previous token.</p>
        </div>
        <div class="scanner-token-value">
            <code id="generated-scanner-token">{{ session('generated_scanner_token') }}</code>
            <button class="secondary" type="button" data-copy-scanner-token>Copy token</button>
        </div>
    </section>
@endif

@if(auth()->user()->hasPermission('scanner-tokens.create'))
    <section class="panel form-panel">
        <div class="panel-heading">
            <div>
                <span class="eyebrow">Registration</span>
                <h2>Add Scanner</h2>
                <p class="muted">Register a scanner to generate its API access token.</p>
            </div>
        </div>

        <form method="post" action="{{ route('admin.scanner-tokens.store') }}" class="form-grid">
            @csrf
            <label>
                Scanner name
                <input type="text" name="name" value="{{ old('name') }}" maxlength="150" required>
            </label>
            <label>
                Device ID
                <input type="text" name="device_id" value="{{ old('device_id') }}" maxlength="150" placeholder="Optional unique hardware identifier">
            </label>
            <div class="form-actions">
                <button class="primary" type="submit">Generate token</button>
            </div>
        </form>
    </section>
@endif

<section class="panel">
    <div class="panel-heading">
        <div>
            <span class="eyebrow">Administration</span>
            <h2>Registered Scanners</h2>
            <p class="muted">Manage scanner identity, access status, and credentials.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Scanner</th>
                    <th>Token prefix</th>
                    <th>Status</th>
                    <th>Last used</th>
                    <th>Created</th>
                    @if(auth()->user()->hasPermission('scanner-tokens.update'))
                        <th>Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
            @forelse($scannerTokens as $scannerToken)
                <tr>
                    <td>
                        <strong>{{ $scannerToken->name }}</strong>
                        <span class="scanner-device-id">{{ $scannerToken->device_id ?: 'No device ID' }}</span>
                    </td>
                    <td><code>{{ $scannerToken->token_prefix }}...</code></td>
                    <td><span class="badge {{ $scannerToken->is_active ? 'valid' : 'invalid' }}">{{ $scannerToken->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td>{{ $scannerToken->last_used_at?->format('Y-m-d H:i') ?: 'Never' }}</td>
                    <td>{{ $scannerToken->created_at?->format('Y-m-d') }}</td>
                    @if(auth()->user()->hasPermission('scanner-tokens.update'))
                        <td>
                            <details class="scanner-token-actions">
                                <summary>Edit</summary>
                                <form method="post" action="{{ route('admin.scanner-tokens.update', $scannerToken) }}" class="form-grid">
                                    @csrf
                                    @method('put')
                                    <label>
                                        Scanner name
                                        <input type="text" name="name" value="{{ $scannerToken->name }}" maxlength="150" required>
                                    </label>
                                    <label>
                                        Device ID
                                        <input type="text" name="device_id" value="{{ $scannerToken->device_id }}" maxlength="150">
                                    </label>
                                    <label class="checkbox">
                                        <input type="checkbox" name="is_active" value="1" @checked($scannerToken->is_active)>
                                        Active
                                    </label>
                                    <button class="primary" type="submit">Save changes</button>
                                </form>
                                <form method="post" action="{{ route('admin.scanner-tokens.regenerate', $scannerToken) }}">
                                    @csrf
                                    <button class="secondary scanner-token-regenerate" type="submit">Regenerate token</button>
                                </form>
                            </details>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ auth()->user()->hasPermission('scanner-tokens.update') ? 6 : 5 }}" class="muted">No scanners have been registered.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $scannerTokens->links() }}
</section>

@if(session('generated_scanner_token'))
<script>
    document.querySelector('[data-copy-scanner-token]')?.addEventListener('click', async function () {
        await navigator.clipboard.writeText(document.getElementById('generated-scanner-token').textContent);
        this.textContent = 'Copied';
    });
</script>
@endif
@endsection
