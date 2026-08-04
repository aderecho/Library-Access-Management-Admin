<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\RfidTransaction;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $branches = $user->role?->slug === 'super-admin'
            ? Branch::where('is_active', true)->orderBy('name')->get()
            : collect([$user->branch])->filter();
        $branchIds = $branches->pluck('id')->map(fn ($id) => (int) $id)->all();
        $cacheKey = 'dashboard:analytics:branches:'.implode(',', $branchIds).':v'.RfidTransaction::cacheVersion();

        $data = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($branches, $branchIds) {
            return $this->dashboardData($branches, $branchIds);
        });

        return view('admin.dashboard', $data);
    }

    private function dashboardData($branches, array $branchIds): array
    {
        $today = Carbon::today();
        $transactions = RfidTransaction::query()->whereIn('branch_id', $branchIds);

        $metrics = [
            'today_scans' => (clone $transactions)->whereDate('scanned_at', $today)->count(),
            'today_valid' => (clone $transactions)->whereDate('scanned_at', $today)->where('status', 'valid')->count(),
            'today_invalid' => (clone $transactions)->whereDate('scanned_at', $today)->where('status', 'invalid')->count(),
            'month_scans' => (clone $transactions)->whereYear('scanned_at', now()->year)
                ->whereMonth('scanned_at', now()->month)->count(),
            'students' => Student::count(),
            'active_students' => Student::where('is_active', true)->count(),
            'employees' => Employee::count(),
            'active_employees' => Employee::where('is_active', true)->count(),
            'admin_users' => User::where('is_active', true)->count(),
        ];

        $months = collect(range(1, 12))->map(fn (int $month) => now()->startOfYear()->month($month));

        $monthlyTransactions = (clone $transactions)
            ->whereBetween('scanned_at', [now()->startOfYear(), now()->endOfYear()])
            ->get(['status', 'scanned_at'])
            ->groupBy(fn (RfidTransaction $transaction) => $transaction->scanned_at->format('Y-m'));

        $chart = $months->map(function (Carbon $month) use ($monthlyTransactions) {
            $transactions = $monthlyTransactions->get($month->format('Y-m'), collect());

            return [
                'label' => $month->format('M'),
                'month' => $month->format('F Y'),
                'total' => $transactions->count(),
                'valid' => $transactions->where('status', 'valid')->count(),
                'invalid' => $transactions->where('status', 'invalid')->count(),
            ];
        });

        $currentMonthIndex = now()->month - 1;
        $currentMonth = $chart->get($currentMonthIndex);
        $previousMonth = $chart->get(max(0, $currentMonthIndex - 1));
        $change = $currentMonth['total'] - $previousMonth['total'];
        $validRate = $currentMonth['total'] > 0
            ? round(($currentMonth['valid'] / $currentMonth['total']) * 100)
            : 0;

        $chartInsights = [
            [
                'number' => '01',
                'text' => $change === 0
                    ? 'Scan activity is unchanged from the previous month.'
                    : sprintf('Monthly scans %s by %s compared with the previous month.', $change > 0 ? 'increased' : 'decreased', number_format(abs($change))),
            ],
            [
                'number' => '02',
                'text' => sprintf('%s%% of scans this month were valid access attempts.', $validRate),
            ],
            [
                'number' => '03',
                'text' => sprintf('%s invalid scans were recorded across the year.', number_format($chart->sum('invalid'))),
            ],
        ];

        $studentDistribution = fn (string $column) => Student::query()
            ->selectRaw("COALESCE(NULLIF({$column}, ''), 'Not specified') as label, COUNT(*) as total")
            ->groupByRaw("COALESCE(NULLIF({$column}, ''), 'Not specified')")
            ->orderByDesc('total')
            ->get()
            ->map(fn ($item) => [
                'label' => $item->label,
                'total' => (int) $item->total,
                'percentage' => $metrics['students'] > 0
                    ? round(((int) $item->total / $metrics['students']) * 100)
                    : 0,
            ]);

        $studentCharts = [
            'programs' => $studentDistribution('program'),
            'colleges' => $studentDistribution('college'),
            'year_levels' => $studentDistribution('year_level'),
        ];

        $branchCounts = (clone $transactions)
            ->whereBetween('scanned_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw("branch_id, COUNT(*) as total, SUM(CASE WHEN status = 'valid' THEN 1 ELSE 0 END) as valid, SUM(CASE WHEN status = 'invalid' THEN 1 ELSE 0 END) as invalid")
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');
        $branchChart = $branches->map(function (Branch $branch) use ($branchCounts) {
            $counts = $branchCounts->get($branch->id);

            return [
                'id' => $branch->id,
                'label' => $branch->name,
                'code' => $branch->code,
                'total' => (int) ($counts?->total ?? 0),
                'valid' => (int) ($counts?->valid ?? 0),
                'invalid' => (int) ($counts?->invalid ?? 0),
            ];
        });

        $recent = (clone $transactions)->with('branch')->latest('scanned_at')->limit(8)->get();

        return compact('metrics', 'chart', 'chartInsights', 'currentMonth', 'studentCharts', 'branchChart', 'recent');
    }
}
