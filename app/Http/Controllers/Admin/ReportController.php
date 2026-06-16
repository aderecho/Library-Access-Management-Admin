<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RfidTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to, $period] = $this->resolveRange($request);

        $query = RfidTransaction::whereBetween('scanned_at', [$from, $to]);

        $summaryKey = implode(':', [
            'reports',
            'summary',
            RfidTransaction::cacheVersion(),
            $from->timestamp,
            $to->timestamp,
        ]);

        $summary = Cache::remember($summaryKey, now()->addMinutes(5), function () use ($query, $period, $from, $to) {
            return [
                'period' => $period,
                'from' => $from,
                'to' => $to,
                'total' => (clone $query)->count(),
                'valid' => (clone $query)->where('status', 'valid')->count(),
                'invalid' => (clone $query)->where('status', 'invalid')->count(),
                'unique_users' => (clone $query)->whereNotNull('campus_id')->distinct('campus_id')->count('campus_id'),
            ];
        });

        $cardholders = (clone $query)
            ->whereNotNull('campus_id')
            ->select([
                'campus_id',
                'cardholder_name',
                'cardholder_type',
                'program',
                'college_department',
            ])
            ->selectRaw('COUNT(*) as frequency')
            ->groupBy([
                'campus_id',
                'cardholder_name',
                'cardholder_type',
                'program',
                'college_department',
            ])
            ->orderByDesc('frequency')
            ->orderBy('cardholder_name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.reports.index', compact('summary', 'cardholders'));
    }

    public function export(Request $request): StreamedResponse
    {
        [$from, $to, $period] = $this->resolveRange($request);

        $filename = "rfid-{$period}-report-".now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($from, $to) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Student/Employee Number', 'Name', 'Program', 'College/Department', 'Frequency']);

            RfidTransaction::whereBetween('scanned_at', [$from, $to])
                ->whereNotNull('campus_id')
                ->select([
                    'campus_id',
                    'cardholder_name',
                    'cardholder_type',
                    'program',
                    'college_department',
                ])
                ->selectRaw('COUNT(*) as frequency')
                ->groupBy([
                    'campus_id',
                    'cardholder_name',
                    'cardholder_type',
                    'program',
                    'college_department',
                ])
                ->orderByDesc('frequency')
                ->orderBy('cardholder_name')
                ->chunk(500, function ($cardholders) use ($output) {
                    foreach ($cardholders as $cardholder) {
                        fputcsv($output, [
                            $cardholder->campus_id,
                            $cardholder->cardholder_name,
                            $cardholder->program,
                            $cardholder->college_department,
                            $cardholder->frequency,
                        ]);
                    }
                });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        [$from, $to, $period] = $this->resolveRange($request);
        $cardholders = $this->cardholderFrequency($from, $to)->get();
        $spreadsheet = new Spreadsheet;
        $reportSheet = $spreadsheet->getActiveSheet()->setTitle('Report');

        $reportSheet->fromArray([
            ['Student/Employee Number', 'Name', 'Program', 'College/Department', 'Frequency'],
            ...$cardholders->map(fn ($cardholder) => [
                $cardholder->campus_id,
                $cardholder->cardholder_name,
                $cardholder->program,
                $cardholder->college_department,
                (int) $cardholder->frequency,
            ])->all(),
        ]);

        $reportSheet->freezePane('A2');
        $reportSheet->setAutoFilter('A1:E'.max(1, $cardholders->count() + 1));
        $reportSheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '68000B']],
        ]);

        foreach (range('A', 'E') as $column) {
            $reportSheet->getColumnDimension($column)->setAutoSize(true);
        }

        $chartSheet = $spreadsheet->createSheet()->setTitle('Graphs');
        $topCardholders = $cardholders->take(10)->values();
        $programs = $this->groupFrequency($cardholders, 'program')->take(10)->values();
        $colleges = $this->groupFrequency($cardholders, 'college_department')->take(10)->values();

        $this->addChartData($chartSheet, 'A1', 'Top Cardholder Frequency', $topCardholders->map(fn ($item) => [
            'label' => $item->cardholder_name,
            'total' => (int) $item->frequency,
        ]));
        $this->addChartData($chartSheet, 'D1', 'Program / Position Frequency', $programs);
        $this->addChartData($chartSheet, 'G1', 'College / Department Frequency', $colleges);

        $this->addBarChart($chartSheet, 'cardholder_frequency', 'Top Cardholder Frequency', 'A', 'B', $topCardholders->count(), 'A14', 'H32');
        $this->addBarChart($chartSheet, 'program_frequency', 'Program / Position Frequency', 'D', 'E', $programs->count(), 'I14', 'P32');
        $this->addBarChart($chartSheet, 'college_frequency', 'College / Department Frequency', 'G', 'H', $colleges->count(), 'A34', 'H52');

        $filename = "rfid-{$period}-report-with-graphs-".now()->format('Ymd-His').'.xlsx';
        $path = tempnam(sys_get_temp_dir(), 'rfid-report-').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);
        $writer->save($path);
        $spreadsheet->disconnectWorksheets();

        return response()->download(
            $path,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    private function cardholderFrequency(Carbon $from, Carbon $to)
    {
        return RfidTransaction::whereBetween('scanned_at', [$from, $to])
            ->whereNotNull('campus_id')
            ->select([
                'campus_id',
                'cardholder_name',
                'cardholder_type',
                'program',
                'college_department',
            ])
            ->selectRaw('COUNT(*) as frequency')
            ->groupBy([
                'campus_id',
                'cardholder_name',
                'cardholder_type',
                'program',
                'college_department',
            ])
            ->orderByDesc('frequency')
            ->orderBy('cardholder_name');
    }

    private function groupFrequency(Collection $cardholders, string $field): Collection
    {
        return $cardholders
            ->groupBy(fn ($cardholder) => filled($cardholder->{$field}) ? $cardholder->{$field} : 'Not specified')
            ->map(fn (Collection $items, string $label) => [
                'label' => $label,
                'total' => $items->sum('frequency'),
            ])
            ->sortByDesc('total');
    }

    private function addChartData($sheet, string $startCell, string $title, Collection $items): void
    {
        [$column, $row] = sscanf($startCell, '%[A-Z]%d');
        $sheet->setCellValue("{$column}{$row}", $title);
        $sheet->setCellValue(chr(ord($column) + 1).$row, 'Frequency');

        foreach ($items as $index => $item) {
            $dataRow = $row + $index + 1;
            $sheet->setCellValue("{$column}{$dataRow}", $item['label']);
            $sheet->setCellValue(chr(ord($column) + 1).$dataRow, $item['total']);
        }
    }

    private function addBarChart($sheet, string $name, string $title, string $labelColumn, string $valueColumn, int $count, string $topLeft, string $bottomRight): void
    {
        if ($count === 0) {
            return;
        }

        $lastRow = $count + 1;
        $labels = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Graphs'!\${$valueColumn}\$1", null, 1)];
        $categories = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Graphs'!\${$labelColumn}\$2:\${$labelColumn}\${$lastRow}", null, $count)];
        $values = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Graphs'!\${$valueColumn}\$2:\${$valueColumn}\${$lastRow}", null, $count)];
        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            [0],
            $labels,
            $categories,
            $values,
            DataSeries::DIRECTION_BAR
        );

        $chart = new Chart(
            $name,
            new Title($title),
            new Legend(Legend::POSITION_RIGHT),
            new PlotArea(null, [$series]),
            true,
            DataSeries::EMPTY_AS_ZERO,
            new Title('Frequency')
        );
        $chart->setTopLeftPosition($topLeft);
        $chart->setBottomRightPosition($bottomRight);
        $sheet->addChart($chart);
    }

    private function resolveRange(Request $request): array
    {
        $period = $request->string('period', 'daily')->toString();

        return match ($period) {
            'monthly' => [now()->startOfMonth(), now()->endOfMonth(), 'monthly'],
            'yearly' => [now()->startOfYear(), now()->endOfYear(), 'yearly'],
            'custom' => [
                Carbon::parse($request->input('from', now()->toDateString()))->startOfDay(),
                Carbon::parse($request->input('to', now()->toDateString()))->endOfDay(),
                'custom',
            ],
            default => [now()->startOfDay(), now()->endOfDay(), 'daily'],
        };
    }
}
