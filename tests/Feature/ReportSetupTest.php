<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\RfidTransaction;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;
use ZipArchive;

class ReportSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_september_report_totals_match_dashboard_including_unidentified_invalid_scans(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 12:00:00'));
        $admin = $this->createReportAdmin();
        $admin->role->update(['permissions' => ['dashboard.view', 'reports.view', 'reports.export']]);

        foreach ([
            ['2026-09-01 00:00:00', 'valid', '2026-001'],
            ['2026-09-30 23:59:59', 'invalid', null],
            ['2026-10-01 00:00:00', 'valid', '2026-002'],
        ] as [$scannedAt, $status, $campusId]) {
            RfidTransaction::create([
                'branch_id' => $this->defaultBranch()->id,
                'rfid_code' => fake()->uuid(),
                'campus_id' => $campusId,
                'cardholder_name' => $campusId ? 'Test Student' : 'Unknown Cardholder',
                'transaction_type' => 'time_in',
                'status' => $status,
                'scanned_at' => $scannedAt,
            ]);
        }

        $dashboard = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $september = $dashboard->viewData('chart')->get(8);
        $this->assertSame(2, $september['total']);
        $this->assertSame(1, $september['valid']);
        $this->assertSame(1, $september['invalid']);

        $report = $this->get(route('admin.reports.index', [
            'period' => 'monthly', 'from' => '2026-09-01', 'to' => '2026-09-30',
        ]))->assertOk();

        foreach (['total', 'valid', 'invalid'] as $metric) {
            $this->assertSame($september[$metric], $report->viewData('summary')[$metric]);
        }
        $this->assertSame(1, (int) $report->viewData('cardholders')->sum('frequency'));
    }

    public function test_selected_september_dates_are_used_for_reports_and_exports_in_october(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 12:00:00'));
        $admin = $this->createReportAdmin();

        foreach ([
            ['2026-08-31 23:59:59', 'August Student'],
            ['2026-09-01 00:00:00', 'September First Student'],
            ['2026-09-30 23:59:59', 'September Last Student'],
            ['2026-10-01 00:00:00', 'October Student'],
        ] as [$scannedAt, $name]) {
            RfidTransaction::create([
                'branch_id' => $this->defaultBranch()->id,
                'cardholder_type' => 'student',
                'rfid_code' => $name,
                'campus_id' => $name,
                'cardholder_name' => $name,
                'transaction_type' => 'time_in',
                'status' => 'valid',
                'scanned_at' => $scannedAt,
            ]);
        }

        $this->actingAs($admin);

        foreach (['daily', 'monthly', 'yearly', 'custom'] as $period) {
            $filters = ['period' => $period, 'from' => '2026-09-01', 'to' => '2026-09-30'];
            $this->get(route('admin.reports.index', $filters))
                ->assertOk()
                ->assertSee('2026-09-01 00:00 to 2026-09-30 23:59')
                ->assertSee('September First Student')
                ->assertSee('September Last Student')
                ->assertDontSee('August Student')
                ->assertDontSee('October Student')
                ->assertViewHas('summary', fn ($summary) => $summary['total'] === 2 && $summary['unique_users'] === 2);

            $csv = $this->get(route('admin.reports.export', $filters))->assertOk()->streamedContent();
            $this->assertStringContainsString('September First Student', $csv);
            $this->assertStringContainsString('September Last Student', $csv);
            $this->assertStringNotContainsString('August Student', $csv);
            $this->assertStringNotContainsString('October Student', $csv);

            $response = $this->get(route('admin.reports.export-excel', $filters))->assertOk();
            $path = $response->baseResponse->getFile()->getPathname();
            $spreadsheet = IOFactory::load($path);
            $rows = $spreadsheet->getSheetByName('Report')->toArray();
            $this->assertSame(['September First Student', 'September Last Student'], array_column(array_slice($rows, 1), 2));
            $spreadsheet->disconnectWorksheets();
            unlink($path);
        }
    }

    public function test_monthly_report_defaults_to_current_month_without_selected_dates(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 12:00:00'));

        $this->actingAs($this->createReportAdmin())
            ->get(route('admin.reports.index', ['period' => 'monthly', 'from' => '', 'to' => '']))
            ->assertOk()
            ->assertSee('2026-10-01 00:00 to 2026-10-31 23:59');
    }

    public function test_report_and_exports_reject_invalid_or_incomplete_date_ranges(): void
    {
        $this->actingAs($this->createReportAdmin());

        foreach (['index', 'export', 'export-excel'] as $action) {
            foreach ([
                [['from' => '2026-09-30', 'to' => '2026-09-01'], 'to'],
                [['from' => 'invalid', 'to' => '2026-09-30'], 'from'],
                [['from' => '2026-09-01'], 'to'],
                [['to' => '2026-09-30'], 'from'],
            ] as [$dates, $error]) {
                $this->getJson(route('admin.reports.'.$action, ['period' => 'monthly', ...$dates]))
                    ->assertUnprocessable()
                    ->assertJsonValidationErrors($error);
            }
        }
    }

    public function test_report_groups_cardholder_scans_and_displays_frequency(): void
    {
        $admin = $this->createReportAdmin();
        $student = Student::create([
            'campus_id' => '2026-10001',
            'rfid_code' => 'report-student-rfid',
            'name' => 'Report Student',
            'program' => 'BS Computer Science',
            'college' => 'College of Science',
            'year_level' => '3rd Year',
            'is_active' => true,
        ]);

        foreach (range(1, 3) as $scan) {
            RfidTransaction::create([
                'branch_id' => $this->defaultBranch()->id,
                'student_id' => $student->id,
                'cardholder_type' => 'student',
                'rfid_code' => $student->rfid_code,
                'campus_id' => $student->campus_id,
                'cardholder_name' => $student->name,
                'program' => $student->program,
                'college_department' => $student->college,
                'year_level' => $student->year_level,
                'transaction_type' => 'time_in',
                'status' => 'valid',
                'message' => 'Library entry recorded successfully.',
                'scanned_at' => now()->subMinutes($scan),
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Student/Employee Number')
            ->assertSee('Branch Entered')
            ->assertSee($this->defaultBranch()->name)
            ->assertSee($student->campus_id)
            ->assertSee($student->program)
            ->assertSee($student->college)
            ->assertSee('Year Level')
            ->assertSee($student->year_level)
            ->assertSee('3');
    }

    public function test_report_csv_uses_the_requested_columns(): void
    {
        $admin = $this->createReportAdmin();

        $response = $this->actingAs($admin)->get(route('admin.reports.export'));

        $response->assertOk();

        $this->assertStringContainsString(
            'Branch,"Student/Employee Number",Name,Program,College/Department,"Year Level",Frequency',
            $response->streamedContent()
        );
    }

    public function test_excel_report_contains_embedded_graphs(): void
    {
        $admin = $this->createReportAdmin();
        $student = Student::create([
            'campus_id' => '2026-10002',
            'rfid_code' => 'excel-report-rfid',
            'name' => 'Excel Report Student',
            'program' => 'BS Biology',
            'college' => 'College of Science',
            'year_level' => '2nd Year',
            'is_active' => true,
        ]);

        RfidTransaction::create([
            'branch_id' => $this->defaultBranch()->id,
            'student_id' => $student->id,
            'cardholder_type' => 'student',
            'rfid_code' => $student->rfid_code,
            'campus_id' => $student->campus_id,
            'cardholder_name' => $student->name,
            'program' => $student->program,
            'college_department' => $student->college,
            'year_level' => $student->year_level,
            'transaction_type' => 'time_in',
            'status' => 'valid',
            'scanned_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.export-excel'));

        $response->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()));
        $this->assertNotFalse($zip->locateName('xl/charts/chart1.xml'));
        $zip->close();

        $spreadsheet = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $reportSheet = $spreadsheet->getSheetByName('Report');
        $this->assertSame('Year Level', $reportSheet->getCell('F1')->getValue());
        $this->assertSame($student->year_level, $reportSheet->getCell('F2')->getValue());
        $spreadsheet->disconnectWorksheets();
    }

    private function createReportAdmin(): User
    {
        $role = Role::create([
            'name' => 'Report Admin',
            'slug' => 'report-admin',
            'permissions' => ['reports.view', 'reports.export'],
        ]);

        return User::create([
            'branch_id' => $this->defaultBranch()->id,
            'name' => 'Report Admin',
            'email' => 'report-admin@example.com',
            'password' => 'password123',
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_branch_report_excludes_entries_from_another_branch(): void
    {
        $admin = $this->createReportAdmin();
        $other = Branch::create(['name' => 'Other Branch', 'code' => 'OTHER']);
        foreach ([[$this->defaultBranch(), 'Visible Branch Student'], [$other, 'Hidden Branch Student']] as [$branch, $name]) {
            RfidTransaction::create([
                'branch_id' => $branch->id,
                'cardholder_type' => 'student',
                'rfid_code' => fake()->unique()->uuid(),
                'campus_id' => fake()->unique()->numerify('2026-#####'),
                'cardholder_name' => $name,
                'transaction_type' => 'time_in',
                'status' => 'valid',
                'scanned_at' => now(),
            ]);
        }

        $this->actingAs($admin)->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Visible Branch Student')
            ->assertDontSee('Hidden Branch Student');
    }
}
