<?php

namespace Tests\Feature;

use App\Models\RfidTransaction;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;
use ZipArchive;

class ReportSetupTest extends TestCase
{
    use RefreshDatabase;

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
        $other = \App\Models\Branch::create(['name' => 'Other Branch', 'code' => 'OTHER']);
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
