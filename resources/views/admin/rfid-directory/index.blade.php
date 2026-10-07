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

    @if(auth()->user()->hasPermission('rfid-directory.update'))
        <details class="rfid-create-options">
            <summary class="button primary">Add New Cardholder</summary>
            <div class="form-actions">
                @foreach(['student' => 'Student', 'employee' => 'Employee'] as $type => $label)
                    <button class="button secondary" type="button" popovertarget="rfid-create-{{ $type }}">Add {{ $label }}</button>
                @endforeach
            </div>
        </details>
        @foreach(['student', 'employee'] as $type)
            @php
                $restore = old('_create_type') === $type;
                $newRecord = (object) [
                    'cardholder_type' => $type,
                    'rfid_code' => $restore ? old('rfid_code') : '',
                    'status' => $restore ? old('status') : ($type === 'student' ? 'Active Student' : 'Active Employee'),
                    'is_active' => $restore ? old('is_active') : true,
                    'identifier' => $restore ? old($type === 'student' ? 'campus_id' : 'employee_number') : '',
                    'first_name' => $restore ? old('first_name') : '',
                    'middle_name' => $restore ? old('middle_name') : '',
                    'last_name' => $restore ? old('last_name') : '',
                    'suffix' => $restore ? old('suffix') : '',
                    'primary_detail' => $restore ? old($type === 'student' ? 'program' : 'position') : '',
                    'secondary_detail' => $restore ? old($type === 'student' ? 'college' : 'office') : '',
                    'year_level' => $restore ? old('year_level') : '',
                ];
            @endphp
            <section id="rfid-create-{{ $type }}" class="rfid-update-panel" popover="auto" aria-labelledby="rfid-create-title-{{ $type }}">
                <div class="rfid-update-header">
                    <div>
                        <span class="eyebrow">{{ ucfirst($type) }} registry</span>
                        <h3 id="rfid-create-title-{{ $type }}">Add New Cardholder</h3>
                    </div>
                    <button type="button" popovertarget="rfid-create-{{ $type }}" popovertargetaction="hide" aria-label="Close add panel">&times;</button>
                </div>
                <form method="post" action="{{ route('admin.rfid-directory.store', $type) }}" class="form-grid rfid-edit-form" style="margin-top:22px">
                    @csrf
                    <input type="hidden" name="_create_type" value="{{ $type }}">
                    @include('admin.rfid-directory.fields', ['record' => $newRecord])
                    <div class="form-actions">
                        <button class="button secondary" type="button" popovertarget="rfid-create-{{ $type }}" popovertargetaction="hide">Cancel</button>
                        <button class="primary" type="submit">Add Cardholder</button>
                    </div>
                </form>
            </section>
            @if($restore && $errors->any())
                <script>document.addEventListener('DOMContentLoaded', () => document.getElementById('rfid-create-{{ $type }}').showPopover());</script>
            @endif
        @endforeach
    @endif

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
                                        @include('admin.rfid-directory.fields', ['record' => $record])
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
