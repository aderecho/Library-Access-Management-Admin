@extends('layouts.admin', ['heading' => 'RFID Directory'])

@section('content')
<nav class="rfid-directory-tabs" aria-label="RFID Directory sections">
    <a
        class="rfid-directory-tab {{ $activeTab === 'directory' ? 'active' : '' }}"
        href="{{ route('admin.rfid-directory.index') }}"
        @if($activeTab === 'directory') aria-current="page" @endif
    >RFID Directory</a>
    <a
        class="rfid-directory-tab {{ $activeTab === 'changes' ? 'active' : '' }}"
        href="{{ route('admin.rfid-directory.index', ['tab' => 'changes']) }}"
        @if($activeTab === 'changes') aria-current="page" @endif
    >Recent RFID Changes</a>
</nav>

@if($activeTab === 'directory')
<section class="panel rfid-directory-panel">
    <div class="panel-heading rfid-directory-heading">
        <div>
            <span class="eyebrow">Cardholder registry</span>
            <h2>RFID Directory</h2>
            <p class="muted">View the RFID assignments of students and employees. Historical scan transactions remain unchanged when an RFID is updated.</p>
        </div>
        <div class="directory-count" aria-label="{{ $directory->total() }} matching records">
            <strong>{{ number_format($directory->total()) }}</strong>
            <span>records</span>
        </div>
    </div>

    <form class="filters directory-filters" method="get" action="{{ route('admin.rfid-directory.index') }}">
        <label class="sr-only" for="directory-search">Search RFID directory</label>
        <input id="directory-search" name="search" value="{{ request('search') }}" placeholder="Search name, ID, or RFID">
        <label class="sr-only" for="directory-type">Cardholder type</label>
        <select id="directory-type" name="type">
            <option value="">All cardholders</option>
            <option value="student" @selected(request('type') === 'student')>Students</option>
            <option value="employee" @selected(request('type') === 'employee')>Employees</option>
        </select>
        <button class="primary" type="submit">Filter</button>
        <a class="button secondary" href="{{ route('admin.rfid-directory.index') }}">Reset</a>
    </form>

    <div class="table-wrap directory-table-wrap" tabindex="0" aria-label="Student and employee RFID records">
        <table class="directory-table">
            <thead>
                <tr>
                    <th>Campus ID / Employee No.</th>
                    <th>Cardholder</th>
                    <th>Type</th>
                    <th>Program / Position</th>
                    <th>College / Office</th>
                    <th>RFID</th>
                    <th>Status</th>
                    @if(auth()->user()->hasPermission('rfid-directory.update'))<th>Action</th>@endif
                </tr>
            </thead>
            <tbody>
            @forelse($directory as $record)
                <tr>
                    <td><strong>{{ $record->identifier }}</strong></td>
                    <td>{{ $record->full_name ?: '—' }}</td>
                    <td><span class="directory-type">{{ ucfirst($record->cardholder_type) }}</span></td>
                    <td>{{ $record->primary_detail ?: '—' }}</td>
                    <td>{{ $record->secondary_detail ?: '—' }}</td>
                    <td><code class="directory-rfid">{{ $record->rfid_code }}</code></td>
                    <td><span class="badge {{ $record->is_active ? 'valid' : 'invalid' }}">{{ $record->status ?: ($record->is_active ? 'Active' : 'Inactive') }}</span></td>
                    @if(auth()->user()->hasPermission('rfid-directory.update'))
                        <td>
                            <div class="rfid-update-disclosure">
                                <button class="button secondary" type="button" popovertarget="rfid-update-{{ $record->cardholder_type }}-{{ $record->id }}">Edit</button>
                                <section id="rfid-update-{{ $record->cardholder_type }}-{{ $record->id }}" class="rfid-update-panel" popover="auto" aria-labelledby="rfid-update-title-{{ $record->cardholder_type }}-{{ $record->id }}">
                                    <div class="rfid-update-header">
                                        <div>
                                            <span class="eyebrow">Cardholder registry</span>
                                            <h3 id="rfid-update-title-{{ $record->cardholder_type }}-{{ $record->id }}">Edit Cardholder</h3>
                                        </div>
                                        <button type="button" popovertarget="rfid-update-{{ $record->cardholder_type }}-{{ $record->id }}" popovertargetaction="hide" aria-label="Close edit panel">&times;</button>
                                    </div>
                                    <div class="rfid-cardholder-summary">
                                        <strong>{{ $record->full_name }}</strong>
                                        <span>{{ $record->identifier }} &middot; {{ ucfirst($record->cardholder_type) }}</span>
                                    </div>
                                    <form method="post" action="{{ route('admin.rfid-directory.update', [$record->cardholder_type, $record->id]) }}" class="form-grid rfid-edit-form">
                                        @csrf
                                        @method('put')
                                        <fieldset class="rfid-edit-section">
                                            <legend>RFID and access</legend>
                                            <div class="rfid-edit-fields">
                                        <label class="rfid-field-wide">RFID
                                            <input name="rfid_code" value="{{ $record->rfid_code }}" maxlength="255" autocomplete="off" required autofocus>
                                        </label>
                                        <label>Status
                                            <input name="status" value="{{ $record->status }}" maxlength="255" required>
                                        </label>
                                        <label>Active
                                            <select name="is_active">
                                                <option value="1" @selected($record->is_active)>Active</option>
                                                <option value="0" @selected(! $record->is_active)>Inactive</option>
                                            </select>
                                        </label>
                                            </div>
                                        </fieldset>
                                        <fieldset class="rfid-edit-section">
                                            <legend>Personal information</legend>
                                            <div class="rfid-edit-fields">
                                        <label class="rfid-field-wide">{{ $record->cardholder_type === 'student' ? 'Campus ID' : 'Employee No.' }}
                                            <input name="{{ $record->cardholder_type === 'student' ? 'campus_id' : 'employee_number' }}" value="{{ $record->identifier }}" maxlength="255" required>
                                        </label>
                                        @foreach(['first_name' => 'First Name', 'middle_name' => 'Middle Name', 'last_name' => 'Last Name', 'suffix' => 'Suffix'] as $field => $label)
                                            <label>{{ $label }}
                                                <input name="{{ $field }}" value="{{ $record->{$field} }}" maxlength="255" @required(in_array($field, ['first_name', 'last_name']))>
                                            </label>
                                        @endforeach
                                            </div>
                                        </fieldset>
                                        <fieldset class="rfid-edit-section">
                                            <legend>{{ $record->cardholder_type === 'student' ? 'Academic details' : 'Employment details' }}</legend>
                                            <div class="rfid-edit-fields">
                                        <label>{{ $record->cardholder_type === 'student' ? 'Program' : 'Position' }}
                                            <input name="{{ $record->cardholder_type === 'student' ? 'program' : 'position' }}" value="{{ $record->primary_detail }}" maxlength="255">
                                        </label>
                                        <label>{{ $record->cardholder_type === 'student' ? 'College' : 'Office' }}
                                            <input name="{{ $record->cardholder_type === 'student' ? 'college' : 'office' }}" value="{{ $record->secondary_detail }}" maxlength="255">
                                        </label>
                                        @if($record->cardholder_type === 'student')
                                            <label>Year Level
                                                <input name="year_level" value="{{ $record->year_level }}" maxlength="255">
                                            </label>
                                        @endif
                                            </div>
                                        </fieldset>
                                        <p class="rfid-audit-note">RFID changes record the old and new values, your user account, and the date and time.</p>
                                        <div class="form-actions">
                                            <button class="button secondary" type="button" popovertarget="rfid-update-{{ $record->cardholder_type }}-{{ $record->id }}" popovertargetaction="hide">Cancel</button>
                                            <button class="primary" type="submit">Save Changes</button>
                                        </div>
                                    </form>
                                </section>
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="8">No RFID directory records found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $directory->onEachSide(1)->links('vendor.pagination.admin') }}
</section>
@else
<section class="panel rfid-audit-panel">
    <div class="panel-heading">
        <div>
            <span class="eyebrow">Audit trail</span>
            <h2>Recent RFID Changes</h2>
            <p class="muted">The previous and replacement RFID values are retained for accountability.</p>
        </div>
    </div>
    <div class="table-wrap" tabindex="0" aria-label="Recent RFID changes">
        <table>
            <thead><tr><th>Date and time</th><th>Cardholder</th><th>ID number</th><th>Old RFID</th><th>New RFID</th><th>Changed by</th></tr></thead>
            <tbody>
            @forelse($recentChanges as $change)
                <tr>
                    <td>{{ $change->created_at?->format('Y-m-d H:i:s') }}</td>
                    <td><strong>{{ $change->cardholder_name }}</strong><br><span class="muted">{{ ucfirst($change->cardholder_type) }}</span></td>
                    <td>{{ $change->cardholder_identifier }}</td>
                    <td><code>{{ $change->old_rfid_code }}</code></td>
                    <td><code>{{ $change->new_rfid_code }}</code></td>
                    <td>{{ $change->changedBy?->full_name ?? 'Unknown user' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No RFID changes have been recorded yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $recentChanges->onEachSide(1)->links('vendor.pagination.admin') }}
</section>
@endif
@endsection
