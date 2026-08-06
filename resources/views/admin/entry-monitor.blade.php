@extends('layouts.admin', ['heading' => 'Entry Monitor', 'title' => 'Entry Monitor | UP Cebu RFID'])

@section('content')
@php
    $isValid = $latest?->status === 'valid';
    $photoFile = $latest?->cardholderPhotoPath();
    $hasPhoto = $photoFile && file_exists(public_path($photoFile));
@endphp

<section class="monitor-control-bar" aria-label="Entry monitor controls">
    <div class="monitor-branch-control">
        <span class="monitor-control-label">Monitoring branch</span>
        @if(auth()->user()->role?->slug === 'super-admin' && $branches->count() > 1)
            <form method="get" action="{{ route('admin.entry-monitor') }}" class="monitor-branch-form">
                <label class="sr-only" for="monitor-branch-select">Monitoring branch</label>
                <select id="monitor-branch-select" name="branch_id" onchange="this.form.submit()">
                    @foreach($branches as $option)
                        <option value="{{ $option->id }}" @selected($branch->id === $option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </form>
        @else
            <strong class="monitor-branch-name">{{ $branch?->name ?? 'All branches' }}</strong>
        @endif
    </div>

    <div class="monitor-toolbar" aria-live="polite">
        <span data-monitor-clock>{{ now()->format('M j, Y, g:i:s A') }}</span>
        <span class="live-signal"><i></i> Live</span>
    </div>
</section>

<div class="entry-monitor" data-live-scan-page data-branch-id="{{ $branch->id }}">
    @if($latest)
        <section class="identity-stage {{ $isValid ? 'is-valid' : 'is-invalid' }}" aria-label="Latest RFID scan">
            <div class="identity-photo-wrap">
                @if($hasPhoto)
                    <img class="identity-photo" src="{{ asset($photoFile) }}" alt="Profile photo of {{ $latest->cardholder_name }}">
                @else
                    <div class="photo-unavailable" role="img" aria-label="Profile photo unavailable">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7 8a7 7 0 0 0-14 0"/></svg>
                        <strong>Photo unavailable</strong><span>Verify the ID details shown</span>
                    </div>
                @endif
            </div>

            <div class="identity-details">
                <div class="entry-result">
                    <span class="result-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">@if($isValid)<path d="m5.5 12.5 4 4 9-9.5"/>@else<path d="M6 6l12 12M18 6 6 18"/>@endif</svg>
                    </span>
                    <div><strong>{{ $isValid ? 'ENTRY VERIFIED' : 'ENTRY DENIED' }}</strong><span>{{ $latest->message }}</span></div>
                </div>

                <h2>{{ $latest->cardholder_name }}</h2>
                <dl class="identity-fields">
                    <div><dt>Library Branch</dt><dd>{{ $latest->branch?->name ?? $branch->name }}</dd></div>
                    <div><dt>UP Cebu ID</dt><dd>{{ $latest->campus_id ?: 'Not registered' }}</dd></div>
                    <div><dt>Scanned / Entered ID</dt><dd class="rfid-value">{{ $latest->rfid_code ?: '—' }}</dd></div>
                    <div><dt>{{ $latest->cardholder_type === 'employee' ? 'Position' : 'Program' }}</dt><dd>{{ $latest->program ?: 'Not available' }}</dd></div>
                </dl>
                <div class="entry-meta">
                    <div><span>Entry time</span><strong>{{ $latest->scanned_at?->format('M j, Y · g:i:s A') }}</strong></div>
                    <div><span>Type</span><strong>{{ ucfirst($latest->cardholder_type) }}</strong></div>
                </div>
            </div>
        </section>

        <section class="recent-entry-list">
            <div class="recent-entry-heading"><h2>Recent Activity</h2><span>Automatically refreshes after every scan</span></div>
            <div class="table-wrap"><table>
                <thead><tr><th>Time</th><th>Branch</th><th>Photo</th><th>Name</th><th>UP Cebu ID</th><th>Scanned / Entered ID</th><th>Program / Position</th><th>Status</th></tr></thead>
                <tbody>@foreach($recent as $transaction)<tr class="activity-row" tabindex="0" role="button" aria-haspopup="dialog" aria-label="View details for {{ $transaction->cardholder_name }}"
                    data-activity-row
                    data-photo="{{ $transaction->cardholderPhotoPath() ? asset($transaction->cardholderPhotoPath()) : '' }}"
                    data-name="{{ $transaction->cardholder_name }}"
                    data-branch="{{ $transaction->branch?->name ?? $branch->name }}"
                    data-campus-id="{{ $transaction->campus_id ?: 'Not registered' }}"
                    data-rfid="{{ $transaction->rfid_code ?: '—' }}"
                    data-program="{{ $transaction->program ?: 'Not available' }}"
                    data-department="{{ $transaction->college_department ?: 'Not available' }}"
                    data-type="{{ ucfirst($transaction->cardholder_type) }}"
                    data-status="{{ $transaction->status }}"
                    data-message="{{ $transaction->message }}"
                    data-scanned-at="{{ $transaction->scanned_at?->format('M j, Y · g:i:s A') }}">
                    <td>{{ $transaction->scanned_at?->format('g:i:s A') }}</td>
                    <td><strong>{{ $transaction->branch?->name ?? $branch->name }}</strong></td>
                    <td>@if($transaction->cardholderPhotoPath())<img class="activity-photo" src="{{ asset($transaction->cardholderPhotoPath()) }}" alt="">@else<span class="activity-photo-placeholder">—</span>@endif</td>
                    <td><strong>{{ $transaction->cardholder_name }}</strong></td><td>{{ $transaction->campus_id ?: '—' }}</td><td class="rfid-table-value">{{ $transaction->rfid_code }}</td><td>{{ $transaction->program ?: '—' }}</td><td><span class="entry-status {{ $transaction->status }}">{{ $transaction->status === 'valid' ? 'Verified' : 'Denied' }}</span></td>
                </tr>@endforeach</tbody>
            </table></div>
        </section>

        <dialog class="activity-dialog" data-activity-dialog aria-labelledby="activity-dialog-title">
            <div class="activity-dialog-layout">
                <div class="activity-dialog-photo-wrap">
                    <img data-dialog-photo alt="">
                    <div class="dialog-photo-fallback" data-dialog-photo-fallback>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7 8a7 7 0 0 0-14 0"/></svg>
                        <span>Photo unavailable</span>
                    </div>
                </div>
                <div class="activity-dialog-content">
                    <button type="button" class="activity-dialog-close" data-dialog-close aria-label="Close details">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                    </button>
                    <div class="dialog-status" data-dialog-status></div>
                    <h2 id="activity-dialog-title" data-dialog-name></h2>
                    <p class="dialog-message" data-dialog-message></p>
                    <dl class="dialog-fields">
                        <div><dt>UP Cebu ID</dt><dd data-dialog-campus-id></dd></div>
                        <div><dt>Library Branch</dt><dd data-dialog-branch></dd></div>
                        <div><dt>Scanned / Entered ID</dt><dd class="dialog-rfid" data-dialog-rfid></dd></div>
                        <div><dt>Program / Position</dt><dd data-dialog-program></dd></div>
                        <div><dt>College / Department</dt><dd data-dialog-department></dd></div>
                        <div><dt>Cardholder type</dt><dd data-dialog-type></dd></div>
                        <div><dt>Scanned at</dt><dd data-dialog-scanned-at></dd></div>
                    </dl>
                </div>
            </div>
        </dialog>
    @else
        <section class="monitor-empty"><div class="monitor-empty-icon">⌁</div><h2>Ready for the next scan</h2><p>No entry has been recorded yet. This screen will update automatically.</p></section>
    @endif
</div>
@endsection
