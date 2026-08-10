@extends('layouts.admin', ['heading' => 'Transaction Logs'])

@section('content')
<div data-live-scan-page>
<section class="panel transaction-log-panel">
    <div class="panel-heading">
        <div><span class="eyebrow">Audit trail</span><h2>RFID Scan Transactions</h2></div>
    </div>

    <form class="filters" method="get">
        <select name="branch_id" @disabled(! auth()->user()->isSuperAdmin())>
            @if(auth()->user()->isSuperAdmin())<option value="">All branches</option>@endif
            @foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((int) $branchId === $branch->id)>{{ $branch->name }}</option>@endforeach
        </select>
        @if(! auth()->user()->isSuperAdmin())<input type="hidden" name="branch_id" value="{{ $branchId }}">@endif
        <input name="search" value="{{ request('search') }}" placeholder="Campus ID, employee number, name, or RFID">
        <select name="status">
            <option value="">All statuses</option>
            <option value="valid" @selected(request('status') === 'valid')>Valid</option>
            <option value="invalid" @selected(request('status') === 'invalid')>Invalid</option>
        </select>
        <input type="date" name="from" value="{{ request('from') }}">
        <input type="date" name="to" value="{{ request('to') }}">
        <button class="primary" type="submit">Filter</button>
        <a class="button secondary" href="{{ route('admin.transactions.index') }}">Reset</a>
    </form>

    <div class="table-wrap transaction-table-wrap" tabindex="0" aria-label="RFID transaction records">
        <table class="transaction-table">
            <thead><tr><th>Scan Time</th><th>Branch Entered</th><th>Campus ID / Employee No.</th><th>Cardholder</th><th>Category</th><th>RFID</th><th>Type</th><th>Status</th><th>Message</th></tr></thead>
            <tbody>
            @forelse($transactions as $transaction)
                <tr>
                    <td>{{ $transaction->scanned_at?->format('Y-m-d H:i:s') }}</td>
                    <td><strong>{{ $transaction->branch?->name ?? 'Unknown branch' }}</strong></td>
                    <td>{{ $transaction->campus_id ?: '—' }}</td>
                    <td>{{ $transaction->cardholder_name }}</td>
                    <td>{{ ucfirst($transaction->cardholder_type) }}</td>
                    <td>{{ $transaction->rfid_code }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $transaction->transaction_type)) }}</td>
                    <td><span class="badge {{ $transaction->status }}">{{ ucfirst($transaction->status) }}</span></td>
                    <td>{{ $transaction->message }}</td>
                </tr>
            @empty
                <tr><td colspan="9">No transactions found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $transactions->onEachSide(1)->links('vendor.pagination.admin') }}
</section>
</div>
@endsection
