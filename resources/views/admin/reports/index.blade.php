@extends('layouts.admin', ['heading' => 'Reports'])

@section('content')
<div data-live-scan-page>
<section class="panel">
    <div class="panel-heading">
        <div><span class="eyebrow">Daily, monthly, and yearly</span><h2>RFID Usage Reports</h2></div>
        @if(auth()->user()->hasPermission('reports.export'))
            <div class="report-export-actions">
                <a class="button secondary" href="{{ route('admin.reports.export', request()->query()) }}">Download CSV</a>
                <a class="button primary" href="{{ route('admin.reports.export-excel', request()->query()) }}">Download Excel with Graphs</a>
            </div>
        @endif
    </div>

    <form class="filters" method="get">
        <select name="period">
            <option value="daily" @selected(request('period', 'daily') === 'daily')>Daily</option>
            <option value="monthly" @selected(request('period') === 'monthly')>Monthly</option>
            <option value="yearly" @selected(request('period') === 'yearly')>Yearly</option>
            <option value="custom" @selected(request('period') === 'custom')>Custom date range</option>
        </select>
        <input type="date" name="from" value="{{ request('from') }}">
        <input type="date" name="to" value="{{ request('to') }}">
        <button class="primary" type="submit">Generate report</button>
    </form>

    <div class="metric-grid report-metrics">
        <article class="metric"><span>Total Scans</span><strong>{{ number_format($summary['total']) }}</strong></article>
        <article class="metric"><span>Valid Access</span><strong>{{ number_format($summary['valid']) }}</strong></article>
        <article class="metric"><span>Invalid Access</span><strong>{{ number_format($summary['invalid']) }}</strong></article>
        <article class="metric"><span>Unique Users</span><strong>{{ number_format($summary['unique_users']) }}</strong></article>
    </div>

    <p class="muted">Range: {{ $summary['from']->format('Y-m-d H:i') }} to {{ $summary['to']->format('Y-m-d H:i') }}</p>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Student/Employee Number</th><th>Name</th><th>Program</th><th>College/Department</th><th>Frequency</th></tr></thead>
            <tbody>
            @forelse($cardholders as $cardholder)
                <tr>
                    <td>{{ $cardholder->campus_id }}</td>
                    <td>{{ $cardholder->cardholder_name }}</td>
                    <td>{{ $cardholder->program ?: '—' }}</td>
                    <td>{{ $cardholder->college_department ?: '—' }}</td>
                    <td>{{ number_format($cardholder->frequency) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No report data found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $cardholders->links() }}
</section>
</div>
@endsection
