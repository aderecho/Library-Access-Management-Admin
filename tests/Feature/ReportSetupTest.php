<?php

namespace Tests\Feature;

use App\Models\RfidTransaction;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'is_active' => true,
        ]);

        foreach (range(1, 3) as $scan) {
            RfidTransaction::create([
                'student_id' => $student->id,
                'cardholder_type' => 'student',
                'rfid_code' => $student->rfid_code,
                'campus_id' => $student->campus_id,
                'cardholder_name' => $student->name,
                'program' => $student->program,
                'college_department' => $student->college,
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
            ->assertSee($student->campus_id)
            ->assertSee($student->program)
            ->assertSee($student->college)
            ->assertSee('3');
    }

    public function test_report_csv_uses_the_requested_columns(): void
    {
        $admin = $this->createReportAdmin();

        $response = $this->actingAs($admin)->get(route('admin.reports.export'));

        $response->assertOk();

        $this->assertStringContainsString(
            '"Student/Employee Number",Name,Program,College/Department,Frequency',
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
            'is_active' => true,
        ]);

        RfidTransaction::create([
            'student_id' => $student->id,
            'cardholder_type' => 'student',
            'rfid_code' => $student->rfid_code,
            'campus_id' => $student->campus_id,
            'cardholder_name' => $student->name,
            'program' => $student->program,
            'college_department' => $student->college,
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
    }

    private function createReportAdmin(): User
    {
        $role = Role::create([
            'name' => 'Report Admin',
            'slug' => 'report-admin',
            'permissions' => ['reports.view', 'reports.export'],
        ]);

        return User::create([
            'name' => 'Report Admin',
            'email' => 'report-admin@example.com',
            'password' => 'password123',
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }
}
