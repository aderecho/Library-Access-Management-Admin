@extends('layouts.admin', ['heading' => 'Dashboard'])

@section('content')
<div data-live-scan-page data-branch-ids="{{ $branchChart->pluck('id')->implode(',') }}">
@php
    $dashboardMetrics = [
        ['key' => 'today_scans', 'label' => 'Scans Today', 'tone' => 'maroon', 'icon' => '<path d="M4 13h3l2-6 4 10 2-4h5"/><path d="M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/>'],
        ['key' => 'today_valid', 'label' => 'Valid Today', 'tone' => 'forest', 'icon' => '<path d="M12 3 5.5 5.7v5.4c0 4.2 2.7 8.1 6.5 9.9 3.8-1.8 6.5-5.7 6.5-9.9V5.7L12 3Z"/><path d="m8.8 12 2.1 2.1 4.5-4.7"/>'],
        ['key' => 'today_invalid', 'label' => 'Invalid Today', 'tone' => 'coral', 'icon' => '<path d="M10.3 4.9 3.2 17.2A2 2 0 0 0 4.9 20h14.2a2 2 0 0 0 1.7-2.8L13.7 4.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4m0 3h.01"/>'],
        ['key' => 'month_scans', 'label' => 'Scans This Month', 'tone' => 'gold', 'icon' => '<path d="M7 3v3m10-3v3M4 9h16"/><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M8 13h3v3H8z"/>'],
        ['key' => 'students', 'label' => 'Registered Students', 'tone' => 'gold', 'icon' => '<path d="m3 10 9-5 9 5-9 5-9-5Z"/><path d="M7 12.8v4.1c2.7 2.1 7.3 2.1 10 0v-4.1M21 10v6"/>'],
        ['key' => 'active_students', 'label' => 'Active Students', 'tone' => 'forest', 'icon' => '<circle cx="9" cy="8" r="3"/><path d="M3.8 19c.5-3.2 2.2-5 5.2-5 1.4 0 2.5.4 3.4 1.2M15 17l2 2 4-5"/>'],
        ['key' => 'employees', 'label' => 'Registered Employees', 'tone' => 'gold', 'icon' => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8" cy="11" r="2"/><path d="M5.5 16c.5-1.6 1.3-2.4 2.5-2.4s2 .8 2.5 2.4M13 10h5m-5 4h4"/>'],
        ['key' => 'active_employees', 'label' => 'Active Employees', 'tone' => 'forest', 'icon' => '<path d="M12 3 5.5 5.7v5.4c0 4.2 2.7 8.1 6.5 9.9 3.8-1.8 6.5-5.7 6.5-9.9V5.7L12 3Z"/><path d="m8.8 12 2.1 2.1 4.5-4.7"/>'],
    ];
@endphp
<nav class="dashboard-tabs" data-dashboard-tabs aria-label="Dashboard sections">
    <div class="dashboard-tablist" role="tablist" aria-label="Dashboard views">
        <button
            class="dashboard-tab is-active"
            id="dashboard-overview-tab"
            type="button"
            role="tab"
            aria-selected="true"
            aria-controls="dashboard-overview-panel"
            data-dashboard-tab="overview"
        >
            <span class="dashboard-tab-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg>
            </span>
            <span class="dashboard-tab-copy"><strong>Overview</strong><small>Summary and latest activity</small></span>
        </button>
        <button
            class="dashboard-tab"
            id="dashboard-entry-analytics-tab"
            type="button"
            role="tab"
            aria-selected="false"
            aria-controls="dashboard-entry-analytics-panel"
            data-dashboard-tab="entry-analytics"
            tabindex="-1"
        >
            <span class="dashboard-tab-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M4 18V9m6 9V5m6 13v-7m4 7H2"/><path d="m4 7 6-4 6 5 4-3"/></svg>
            </span>
            <span class="dashboard-tab-copy"><strong>Entry Analytics</strong><small>Monthly and branch trends</small></span>
        </button>
        <button
            class="dashboard-tab"
            id="dashboard-students-tab"
            type="button"
            role="tab"
            aria-selected="false"
            aria-controls="dashboard-students-panel"
            data-dashboard-tab="students"
            tabindex="-1"
        >
            <span class="dashboard-tab-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="m3 10 9-5 9 5-9 5-9-5Z"/><path d="M7 12.8v4.1c2.7 2.1 7.3 2.1 10 0v-4.1M21 10v6"/></svg>
            </span>
            <span class="dashboard-tab-copy"><strong>Student Distribution</strong><small>Program, college, and year</small></span>
        </button>
    </div>
</nav>

<section
    class="dashboard-tab-panel is-active"
    id="dashboard-overview-panel"
    role="tabpanel"
    aria-labelledby="dashboard-overview-tab"
    data-dashboard-panel="overview"
>
<div class="metric-grid">
    @foreach($dashboardMetrics as $metric)
        <article class="metric metric-{{ $metric['tone'] }}" data-dashboard-metric="{{ $metric['key'] }}">
            <span class="metric-orbit" aria-hidden="true"></span>
            <div class="metric-topline">
                <span class="metric-label">{{ $metric['label'] }}</span>
                <span class="metric-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">{!! $metric['icon'] !!}</svg>
                </span>
            </div>
            <div class="metric-value">
                <strong>{{ number_format($metrics[$metric['key']]) }}</strong>
                <span class="metric-pulse" aria-hidden="true"><i></i></span>
            </div>
        </article>
    @endforeach
</div>

<div class="two-column dashboard-lower">
    <section class="panel dashboard-summary-panel">
        <div class="panel-heading">
            <div>
                <span class="eyebrow">Current month</span>
                <h2>Access Summary</h2>
                <p class="muted">A quick view of this month&rsquo;s RFID access results.</p>
            </div>
        </div>
        <div class="access-summary-feature">
            <div class="access-total">
                <span class="access-total-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M4 13h3l2-6 4 10 2-4h5"/><path d="M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/></svg>
                </span>
                <div>
                    <span>Total access attempts</span>
                    <strong>{{ number_format($currentMonth['total']) }}</strong>
                    <small>recorded this month</small>
                </div>
            </div>
            <div
                class="access-rate"
                style="--access-rate: {{ $validRate * 3.6 }}deg"
                role="img"
                aria-label="{{ $validRate }} percent of access attempts were verified"
            >
                <div><strong>{{ $validRate }}%</strong><span>verified</span></div>
            </div>
        </div>
        <div class="access-breakdown">
            <article class="access-breakdown-item is-valid">
                <span class="access-breakdown-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="m6.5 12.5 3.4 3.4 7.6-8"/></svg>
                </span>
                <div><span>Verified entries</span><strong>{{ number_format($currentMonth['valid']) }}</strong></div>
            </article>
            <article class="access-breakdown-item is-invalid">
                <span class="access-breakdown-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="m8 8 8 8m0-8-8 8"/></svg>
                </span>
                <div><span>Denied entries</span><strong>{{ number_format($currentMonth['invalid']) }}</strong></div>
            </article>
        </div>
        <div class="access-summary-insight">
            <span aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M12 3 5.5 5.7v5.4c0 4.2 2.7 8.1 6.5 9.9 3.8-1.8 6.5-5.7 6.5-9.9V5.7L12 3Z"/><path d="m8.8 12 2.1 2.1 4.5-4.7"/></svg>
            </span>
            <div>
                <strong>Monthly verification</strong>
                <p>
                    @if($currentMonth['total'] > 0)
                        {{ number_format($currentMonth['valid']) }} of {{ number_format($currentMonth['total']) }} access attempts were successfully verified.
                    @else
                        No access attempts have been recorded this month.
                    @endif
                </p>
            </div>
        </div>
    </section>

    <section class="panel recent-transactions-panel">
        <div class="panel-heading">
            <div>
                <span class="eyebrow">Latest system records</span>
                <h2>Recent Transactions</h2>
                <p class="muted">The five most recent access attempts across your assigned branches.</p>
            </div>
            <a class="panel-action" href="{{ route('admin.transactions.index') }}">
                <span>View all</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>
            </a>
        </div>
        <div class="recent-transaction-list">
            @forelse($recent as $transaction)
                @php
                    $isValidTransaction = $transaction->status === 'valid';
                @endphp
                <article class="recent-transaction-row">
                    <span class="recent-transaction-icon {{ $isValidTransaction ? 'is-valid' : 'is-invalid' }}" aria-hidden="true">
                        @if($isValidTransaction)
                            <svg viewBox="0 0 24 24"><path d="m6.5 12.5 3.4 3.4 7.6-8"/></svg>
                        @else
                            <svg viewBox="0 0 24 24"><path d="m8 8 8 8m0-8-8 8"/></svg>
                        @endif
                    </span>
                    <div class="recent-transaction-person">
                        <strong>{{ $transaction->cardholder_name }}</strong>
                        <time datetime="{{ $transaction->scanned_at?->toIso8601String() }}">{{ $transaction->scanned_at?->format('M d, Y · h:i A') }}</time>
                    </div>
                    <div class="recent-transaction-branch">
                        <span>Branch</span>
                        <strong>{{ $transaction->branch?->name ?? 'Unknown branch' }}</strong>
                    </div>
                    <span class="recent-transaction-status {{ $isValidTransaction ? 'is-valid' : 'is-invalid' }}">
                        {{ $isValidTransaction ? 'Verified' : 'Denied' }}
                    </span>
                </article>
            @empty
                <div class="recent-transactions-empty">
                    <span aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M4 13h3l2-6 4 10 2-4h5"/><path d="M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/></svg>
                    </span>
                    <div><strong>No recent transactions</strong><p>New access attempts will appear here automatically.</p></div>
                </div>
            @endforelse
        </div>
    </section>
</div>
</section>

<section
    class="dashboard-tab-panel"
    id="dashboard-entry-analytics-panel"
    role="tabpanel"
    aria-labelledby="dashboard-entry-analytics-tab"
    data-dashboard-panel="entry-analytics"
    hidden
>
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
        $chartCoordinates = fn (string $key) => $chart->values()->map(
            fn ($item, $index) => [
                'x' => $left + ($index * $xStep),
                'y' => $top + $plotHeight - (($item[$key] / $chartMax) * $plotHeight),
            ]
        )->all();
        $chartPath = function (string $key) use ($chartCoordinates): string {
            $points = $chartCoordinates($key);

            if (count($points) === 0) {
                return '';
            }

            $path = sprintf('M %.2f %.2f', $points[0]['x'], $points[0]['y']);

            for ($index = 0; $index < count($points) - 1; $index++) {
                $previous = $points[max(0, $index - 1)];
                $current = $points[$index];
                $next = $points[$index + 1];
                $following = $points[min(count($points) - 1, $index + 2)];
                $controlOneX = $current['x'] + (($next['x'] - $previous['x']) / 6);
                $controlOneY = $current['y'] + (($next['y'] - $previous['y']) / 6);
                $controlTwoX = $next['x'] - (($following['x'] - $current['x']) / 6);
                $controlTwoY = $next['y'] - (($following['y'] - $current['y']) / 6);
                $segmentMinY = min($current['y'], $next['y']);
                $segmentMaxY = max($current['y'], $next['y']);
                $controlOneY = max($segmentMinY, min($segmentMaxY, $controlOneY));
                $controlTwoY = max($segmentMinY, min($segmentMaxY, $controlTwoY));

                $path .= sprintf(
                    ' C %.2f %.2f, %.2f %.2f, %.2f %.2f',
                    $controlOneX,
                    $controlOneY,
                    $controlTwoX,
                    $controlTwoY,
                    $next['x'],
                    $next['y']
                );
            }

            return $path;
        };
        $chartAreaPath = function (string $key) use ($chartCoordinates, $chartPath, $top, $plotHeight): string {
            $points = $chartCoordinates($key);

            if (count($points) === 0) {
                return '';
            }

            $baseline = $top + $plotHeight;
            $last = $points[count($points) - 1];
            $first = $points[0];

            return $chartPath($key).sprintf(
                ' L %.2f %.2f L %.2f %.2f Z',
                $last['x'],
                $baseline,
                $first['x'],
                $baseline
            );
        };
    @endphp

    <div class="comparison-layout">
        <div
            class="line-chart-wrap"
            data-monthly-chart-scroll
            data-current-month-index="{{ now()->month - 1 }}"
            tabindex="0"
            aria-label="Monthly RFID scan chart. Scroll horizontally to view all months."
        >
            <svg class="line-chart" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" role="img" aria-labelledby="monthly-chart-title monthly-chart-description">
                <title id="monthly-chart-title">Monthly RFID scan comparison</title>
                <desc id="monthly-chart-description">Smooth lines compare total, valid, and invalid RFID scans by month. Exact values are available in the expandable table below.</desc>
                <defs>
                    <linearGradient id="total-line-gradient" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0" stop-color="#68000b"/><stop offset="1" stop-color="#a51f30"/>
                    </linearGradient>
                    <linearGradient id="valid-line-gradient" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0" stop-color="#0f4738"/><stop offset="1" stop-color="#3f8a71"/>
                    </linearGradient>
                    <linearGradient id="invalid-line-gradient" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0" stop-color="#b77b13"/><stop offset="1" stop-color="#efbd55"/>
                    </linearGradient>
                    <linearGradient id="total-area-gradient" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#8c1423" stop-opacity=".2"/><stop offset="1" stop-color="#8c1423" stop-opacity="0"/>
                    </linearGradient>
                    <linearGradient id="valid-area-gradient" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#2c745e" stop-opacity=".16"/><stop offset="1" stop-color="#2c745e" stop-opacity="0"/>
                    </linearGradient>
                    <linearGradient id="invalid-area-gradient" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#d99b22" stop-opacity=".15"/><stop offset="1" stop-color="#d99b22" stop-opacity="0"/>
                    </linearGradient>
                    <filter id="soft-line-glow" x="-20%" y="-20%" width="140%" height="140%">
                        <feGaussianBlur stdDeviation="2.2" result="blur"/>
                        <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
                    </filter>
                </defs>
                <rect class="chart-plot-bg" x="{{ $left }}" y="{{ $top }}" width="{{ $plotWidth }}" height="{{ $plotHeight }}" rx="12"/>
                @foreach(range(0, 4) as $gridIndex)
                    @php
                        $gridY = $top + (($plotHeight / 4) * $gridIndex);
                        $gridValue = round($chartMax - (($chartMax / 4) * $gridIndex));
                    @endphp
                    <line class="chart-grid-line" x1="{{ $left }}" y1="{{ $gridY }}" x2="{{ $chartWidth - $right }}" y2="{{ $gridY }}"/>
                    <text class="chart-axis-text" x="{{ $left - 9 }}" y="{{ $gridY + 4 }}" text-anchor="end">{{ number_format($gridValue) }}</text>
                @endforeach

                @foreach($chart->values() as $index => $item)
                    @php($gridX = $left + ($index * $xStep))
                    <line class="chart-grid-line chart-grid-vertical" x1="{{ $gridX }}" y1="{{ $top }}" x2="{{ $gridX }}" y2="{{ $top + $plotHeight }}"/>
                @endforeach

                <path class="chart-area total-area" d="{{ $chartAreaPath('total') }}"/>
                <path class="chart-area valid-area" d="{{ $chartAreaPath('valid') }}"/>
                <path class="chart-area invalid-area" d="{{ $chartAreaPath('invalid') }}"/>
                <path class="chart-line total-line" d="{{ $chartPath('total') }}"/>
                <path class="chart-line valid-line" d="{{ $chartPath('valid') }}"/>
                <path class="chart-line invalid-line" d="{{ $chartPath('invalid') }}"/>

                @foreach($chart->values() as $index => $item)
                    @php($x = $left + ($index * $xStep))
                    @foreach(['total', 'valid', 'invalid'] as $series)
                        @php($y = $top + $plotHeight - (($item[$series] / $chartMax) * $plotHeight))
                        <g class="chart-point-group">
                            <circle class="chart-point-halo {{ $series }}-halo" cx="{{ $x }}" cy="{{ $y }}" r="8"/>
                            <circle class="chart-point {{ $series }}-point" cx="{{ $x }}" cy="{{ $y }}" r="4.5">
                                <title>{{ $item['month'] }} {{ ucfirst($series) }}: {{ $item[$series] }}</title>
                            </circle>
                        </g>
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

    <details class="analytics-data-disclosure">
        <summary>
            <span class="analytics-data-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M4 5h16M4 12h16M4 19h16"/><path d="M8 3v18M16 3v18"/></svg>
            </span>
            <span><strong>View exact monthly values</strong><small>Open the accessible data table for all chart points.</small></span>
            <svg class="analytics-data-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
        </summary>
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
    </details>
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
    <div class="branch-bars" role="list" aria-label="Entries per library branch for {{ now()->format('F Y') }}">
        @forelse($branchChart as $item)
            <article class="branch-bar-row" role="listitem" aria-label="{{ $item['label'] }}: {{ number_format($item['total']) }} total entries, {{ number_format($item['valid']) }} verified, {{ number_format($item['invalid']) }} denied">
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
    <details class="analytics-data-disclosure branch-data-disclosure">
        <summary>
            <span class="analytics-data-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M4 5h16M4 12h16M4 19h16"/><path d="M8 3v18M16 3v18"/></svg>
            </span>
            <span><strong>View exact branch values</strong><small>Open the accessible branch comparison table.</small></span>
            <svg class="analytics-data-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
        </summary>
        <div class="comparison-table-wrap">
            <table class="comparison-table">
                <thead><tr><th>Branch</th><th>Total entries</th><th>Verified</th><th>Denied</th></tr></thead>
                <tbody>@foreach($branchChart as $item)<tr><th>{{ $item['label'] }}</th><td>{{ number_format($item['total']) }}</td><td>{{ number_format($item['valid']) }}</td><td>{{ number_format($item['invalid']) }}</td></tr>@endforeach</tbody>
            </table>
        </div>
    </details>
</section>

</section>

<section
    class="dashboard-tab-panel"
    id="dashboard-students-panel"
    role="tabpanel"
    aria-labelledby="dashboard-students-tab"
    data-dashboard-panel="students"
    hidden
>
<section class="panel student-distribution-panel">
    <div class="panel-heading distribution-heading">
        <div>
            <span class="eyebrow">Registered student profile</span>
            <h2>Student Distribution</h2>
            <p class="muted">Breakdown of registered students by program, college, and year level.</p>
        </div>
        <div class="distribution-overview" aria-label="{{ number_format($metrics['students']) }} registered students">
            <strong>{{ number_format($metrics['students']) }}</strong>
            <span>registered students</span>
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
                    <div class="distribution-card-title">
                        <span class="distribution-card-index">{{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}</span>
                        <div>
                            <h3>{{ $title }}</h3>
                            <p>{{ number_format($studentCharts[$key]->count()) }} categories represented</p>
                        </div>
                    </div>
                    <div class="distribution-card-total">
                        <strong>{{ number_format($studentCharts[$key]->sum('total')) }}</strong>
                        <span>students</span>
                    </div>
                </div>

                <div class="distribution-bars" aria-label="Student distribution by {{ $description }}">
                    @forelse($studentCharts[$key] as $item)
                        <div class="distribution-row">
                            <div class="distribution-label">
                                <span title="{{ $item['label'] }}">{{ $item['label'] }}</span>
                                <span class="distribution-value">
                                    <strong>{{ number_format($item['total']) }}</strong>
                                    <small>{{ $item['percentage'] }}%</small>
                                </span>
                            </div>
                            <div
                                class="distribution-track"
                                role="progressbar"
                                aria-label="{{ $item['label'] }}: {{ number_format($item['total']) }} students, {{ $item['percentage'] }} percent"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-valuenow="{{ $item['percentage'] }}"
                            >
                                <span style="width: {{ $item['percentage'] }}%"></span>
                            </div>
                        </div>
                    @empty
                        <p class="muted">No student data found.</p>
                    @endforelse
                </div>
            </article>
        @endforeach
    </div>
</section>
</section>
</div>
@vite('resources/js/dashboard-tabs.js')
@endsection
