@extends('layouts.admin', ['heading' => 'Dashboard'])

@section('content')
<div data-live-scan-page data-branch-ids="{{ $branchChart->pluck('id')->implode(',') }}">
<div class="metric-grid">
    <article class="metric"><span>Scans Today</span><strong>{{ number_format($metrics['today_scans']) }}</strong></article>
    <article class="metric"><span>Valid Today</span><strong>{{ number_format($metrics['today_valid']) }}</strong></article>
    <article class="metric"><span>Invalid Today</span><strong>{{ number_format($metrics['today_invalid']) }}</strong></article>
    <article class="metric"><span>Scans This Month</span><strong>{{ number_format($metrics['month_scans']) }}</strong></article>
    <article class="metric"><span>Registered Students</span><strong>{{ number_format($metrics['students']) }}</strong></article>
    <article class="metric"><span>Active Students</span><strong>{{ number_format($metrics['active_students']) }}</strong></article>
    <article class="metric"><span>Registered Employees</span><strong>{{ number_format($metrics['employees']) }}</strong></article>
    <article class="metric"><span>Active Employees</span><strong>{{ number_format($metrics['active_employees']) }}</strong></article>
</div>

<section class="panel comparison-panel">
    <div class="panel-heading comparison-heading">
        <div>
            <span class="eyebrow">Annual access activity</span>
            <h2>Monthly RFID Scan Comparison</h2>
            <p class="muted">Comparison of total, valid, and invalid RFID scans.</p>
        </div>
        <div class="chart-legend" aria-label="Chart legend">
            <span><i class="legend-total"></i>Total Scans</span>
            <span><i class="legend-valid"></i>Valid Scans</span>
            <span><i class="legend-invalid"></i>Invalid Scans</span>
        </div>
    </div>

    @php
        $chartMax = max(1, $chart->max('total'));
        $chartWidth = 720;
        $chartHeight = 300;
        $left = 54;
        $right = 18;
        $top = 18;
        $bottom = 42;
        $plotWidth = $chartWidth - $left - $right;
        $plotHeight = $chartHeight - $top - $bottom;
        $xStep = $plotWidth / max(1, $chart->count() - 1);
        $chartPoints = fn (string $key) => $chart->values()->map(
            fn ($item, $index) => ($left + ($index * $xStep)).','.($top + $plotHeight - (($item[$key] / $chartMax) * $plotHeight))
        )->implode(' ');
    @endphp

    <div class="comparison-layout">
        <div class="line-chart-wrap">
            <svg class="line-chart" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" role="img" aria-label="Monthly RFID scan comparison line chart">
                @foreach(range(0, 4) as $gridIndex)
                    @php
                        $gridY = $top + (($plotHeight / 4) * $gridIndex);
                        $gridValue = round($chartMax - (($chartMax / 4) * $gridIndex));
                    @endphp
                    <line class="chart-grid-line" x1="{{ $left }}" y1="{{ $gridY }}" x2="{{ $chartWidth - $right }}" y2="{{ $gridY }}"/>
                    <text class="chart-axis-text" x="{{ $left - 9 }}" y="{{ $gridY + 4 }}" text-anchor="end">{{ number_format($gridValue) }}</text>
                @endforeach

                <polyline class="chart-line total-line" points="{{ $chartPoints('total') }}"/>
                <polyline class="chart-line valid-line" points="{{ $chartPoints('valid') }}"/>
                <polyline class="chart-line invalid-line" points="{{ $chartPoints('invalid') }}"/>

                @foreach($chart->values() as $index => $item)
                    @php($x = $left + ($index * $xStep))
                    @foreach(['total', 'valid', 'invalid'] as $series)
                        @php($y = $top + $plotHeight - (($item[$series] / $chartMax) * $plotHeight))
                        <circle class="chart-point {{ $series }}-point" cx="{{ $x }}" cy="{{ $y }}" r="4">
                            <title>{{ $item['month'] }} {{ ucfirst($series) }}: {{ $item[$series] }}</title>
                        </circle>
                    @endforeach
                    <text class="chart-axis-text chart-month" x="{{ $x }}" y="{{ $chartHeight - 14 }}" text-anchor="middle">{{ $item['label'] }}</text>
                @endforeach
            </svg>
        </div>

        <aside class="chart-insights">
            <h3>Key Takeaways</h3>
            @foreach($chartInsights as $insight)
                <div class="insight">
                    <span>{{ $insight['number'] }}</span>
                    <p>{{ $insight['text'] }}</p>
                </div>
            @endforeach
        </aside>
    </div>

    <div class="comparison-table-wrap">
        <table class="comparison-table">
            <thead>
                <tr>
                    <th>Scan Type</th>
                    @foreach($chart as $item)<th>{{ $item['label'] }}</th>@endforeach
                </tr>
            </thead>
            <tbody>
                @foreach(['total' => 'Total Scans', 'valid' => 'Valid Scans', 'invalid' => 'Invalid Scans'] as $key => $label)
                    <tr>
                        <th><span class="table-series {{ $key }}"></span>{{ $label }}</th>
                        @foreach($chart as $item)<td>{{ number_format($item[$key]) }}</td>@endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

<section class="panel branch-comparison-panel">
    <div class="panel-heading">
        <div>
            <span class="eyebrow">Current month by facility</span>
            <h2>Entries per Library Branch</h2>
            <p class="muted">Direct comparison of verified and denied RFID entries for {{ now()->format('F Y') }}.</p>
        </div>
    </div>
    @php($branchMax = max(1, $branchChart->max('total')))
    <div class="branch-bars" role="img" aria-label="Entries per library branch for {{ now()->format('F Y') }}">
        @forelse($branchChart as $item)
            <article class="branch-bar-row">
                <div class="branch-bar-label"><strong>{{ $item['label'] }}</strong><span>{{ $item['code'] }}</span></div>
                <div class="branch-bar-track" aria-hidden="true"><span style="width: {{ round(($item['total'] / $branchMax) * 100) }}%"></span></div>
                <div class="branch-bar-values">
                    <strong>{{ number_format($item['total']) }}</strong>
                    <span>{{ number_format($item['valid']) }} verified · {{ number_format($item['invalid']) }} denied</span>
                </div>
            </article>
        @empty
            <p class="muted">No active branches are available.</p>
        @endforelse
    </div>
    <div class="comparison-table-wrap">
        <table class="comparison-table">
            <thead><tr><th>Branch</th><th>Total entries</th><th>Verified</th><th>Denied</th></tr></thead>
            <tbody>@foreach($branchChart as $item)<tr><th>{{ $item['label'] }}</th><td>{{ number_format($item['total']) }}</td><td>{{ number_format($item['valid']) }}</td><td>{{ number_format($item['invalid']) }}</td></tr>@endforeach</tbody>
        </table>
    </div>
</section>

<section class="panel student-distribution-panel">
    <div class="panel-heading">
        <div>
            <span class="eyebrow">Registered student profile</span>
            <h2>Student Distribution</h2>
            <p class="muted">Breakdown of registered students by program, college, and year level.</p>
        </div>
    </div>

    <div class="distribution-grid">
        @foreach([
            'programs' => ['Program Distribution', 'program'],
            'colleges' => ['College Distribution', 'college'],
            'year_levels' => ['Year Level Distribution', 'year level'],
        ] as $key => [$title, $description])
            <article class="distribution-card">
                <div class="distribution-card-heading">
                    <h3>{{ $title }}</h3>
                    <span>{{ number_format($studentCharts[$key]->sum('total')) }} students</span>
                </div>

                <div class="distribution-bars" role="img" aria-label="Student distribution by {{ $description }}">
                    @forelse($studentCharts[$key] as $item)
                        <div class="distribution-row">
                            <div class="distribution-label">
                                <span title="{{ $item['label'] }}">{{ $item['label'] }}</span>
                                <strong>{{ number_format($item['total']) }}</strong>
                            </div>
                            <div class="distribution-track">
                                <span style="width: {{ $item['percentage'] }}%"></span>
                            </div>
                            <small>{{ $item['percentage'] }}%</small>
                        </div>
                    @empty
                        <p class="muted">No student data found.</p>
                    @endforelse
                </div>
            </article>
        @endforeach
    </div>
</section>

<div class="two-column dashboard-lower">
    <section class="panel">
        <div class="panel-heading">
            <div><span class="eyebrow">Current month</span><h2>Access Summary</h2></div>
        </div>
        <div class="summary-list">
            <div><span>Total scans</span><strong>{{ number_format($currentMonth['total']) }}</strong></div>
            <div><span>Valid scans</span><strong>{{ number_format($currentMonth['valid']) }}</strong></div>
            <div><span>Invalid scans</span><strong>{{ number_format($currentMonth['invalid']) }}</strong></div>
        </div>
    </section>

    <section class="panel">
        <div class="panel-heading">
            <div><span class="eyebrow">Latest system records</span><h2>Recent Transactions</h2></div>
            <a href="{{ route('admin.transactions.index') }}">View all</a>
        </div>
        <div class="compact-list">
            @forelse($recent as $transaction)
                <div>
                    <span class="status-dot {{ $transaction->status }}"></span>
                    <strong>{{ $transaction->cardholder_name }}</strong>
                    <span>{{ $transaction->branch?->name ?? 'Unknown branch' }}</span>
                    <small>{{ $transaction->scanned_at?->format('M d, H:i') }}</small>
                </div>
            @empty
                <p class="muted">No transactions found.</p>
            @endforelse
        </div>
    </section>
</div>
</div>
@endsection
