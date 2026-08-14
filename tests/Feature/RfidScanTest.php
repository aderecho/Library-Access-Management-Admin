<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\ScannerToken;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RfidScanTest extends TestCase
{
    use RefreshDatabase;

    private string $scannerToken;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config()->set('services.amis.base_url', 'http://localhost:8001');
        config()->set('services.amis.origin', 'https://rfid-test.example');

        $this->scannerToken = 'upcebu_scanner_test_token';

        ScannerToken::create([
            'branch_id' => $this->defaultBranch()->id,
            'name' => 'Test Scanner',
            'token_hash' => hash('sha256', $this->scannerToken),
            'token_prefix' => substr($this->scannerToken, 0, 22),
            'is_active' => true,
        ]);
    }

    public function test_active_employee_rfid_is_accepted(): void
    {
        $employee = Employee::create([
            'employee_number' => 'UPCEBU-EMP-100',
            'rfid_code' => 'employee-active-rfid',
            'first_name' => 'Alex',
            'middle_name' => 'Rivera',
            'last_name' => 'Employee',
            'suffix' => 'Jr.',
            'position' => 'Faculty Member',
            'office' => 'College of Science',
            'status' => 'Active Employee',
            'is_active' => true,
        ]);

        $response = $this->withHeader('X-Scanner-Token', $this->scannerToken)->postJson('/api/rfid/scan', [
            'rfid_code' => $employee->rfid_code,
        ]);

        $response->assertOk()
            ->assertJson([
                'cardholderType' => 'employee',
                'campusId' => $employee->employee_number,
                'name' => $employee->full_name,
                'firstName' => 'Alex',
                'middleName' => 'Rivera',
                'lastName' => 'Employee',
                'suffix' => 'Jr.',
                'program' => $employee->position,
                'college' => $employee->office,
                'yearLevel' => 'Employee',
                'status' => $employee->status,
                'valid' => true,
            ]);

        $this->assertDatabaseHas('rfid_transactions', [
            'employee_id' => $employee->id,
            'student_id' => null,
            'cardholder_type' => 'employee',
            'cardholder_name' => $employee->full_name,
            'campus_id' => $employee->employee_number,
            'status' => 'valid',
        ]);
    }

    public function test_every_new_entry_is_recorded_in_philippine_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-14 16:54:22', 'UTC'));

        try {
            $employee = Employee::create([
                'employee_number' => 'UPCEBU-EMP-PHT',
                'rfid_code' => 'employee-pht-rfid',
                'first_name' => 'Philippine',
                'last_name' => 'Time',
                'position' => 'Staff',
                'office' => 'Library',
                'status' => 'Active Employee',
                'is_active' => true,
            ]);

            $this->withHeader('X-Scanner-Token', $this->scannerToken)
                ->postJson('/api/rfid/scan', ['rfid_code' => $employee->rfid_code])
                ->assertOk();

            $this->assertDatabaseHas('rfid_transactions', [
                'employee_id' => $employee->id,
                'scanned_at' => '2026-08-15 00:54:22',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_inactive_employee_rfid_is_denied(): void
    {
        $employee = Employee::create([
            'employee_number' => 'UPCEBU-EMP-101',
            'rfid_code' => 'employee-inactive-rfid',
            'name' => 'Inactive Employee',
            'status' => 'Inactive Employee',
            'is_active' => false,
        ]);

        $response = $this->withHeader('X-Scanner-Token', $this->scannerToken)->postJson('/api/rfid/scan', [
            'rfid_code' => $employee->rfid_code,
        ]);

        $response->assertOk()
            ->assertJson([
                'cardholderType' => 'employee',
                'valid' => false,
                'message' => 'Access denied. Please proceed to the help desk.',
            ]);

        $this->assertDatabaseHas('rfid_transactions', [
            'employee_id' => $employee->id,
            'cardholder_type' => 'employee',
            'status' => 'invalid',
        ]);
    }

    public function test_student_campus_id_is_accepted_as_identifier(): void
    {
        $student = Student::create([
            'campus_id' => '2026-10001',
            'rfid_code' => 'student-rfid-10001',
            'name' => 'Campus ID Student',
            'year_level' => '4th Year',
            'is_active' => true,
        ]);

        Http::fake([
            'http://localhost:8001/api/student-info/'.$student->campus_id => Http::response([
                'student' => [[
                    'campus_id' => $student->campus_id,
                    'first_name' => 'Campus',
                    'middle_name' => 'ID',
                    'last_name' => 'Student',
                    'academic_program_id' => 'BSCS',
                    'title' => 'BS Computer Science',
                    'classification' => '4th Year',
                    'status' => 'Active Student',
                ]],
            ]),
        ]);

        $this->withHeader('X-Scanner-Token', $this->scannerToken)
            ->postJson('/api/rfid/scan', ['identifier' => $student->campus_id])
            ->assertOk()
            ->assertJson([
                'cardholderType' => 'student',
                'campusId' => $student->campus_id,
                'rfidCode' => $student->rfid_code,
                'yearLevel' => $student->year_level,
                'verificationSource' => 'amis',
                'verificationStatus' => 'verified',
                'valid' => true,
            ]);

        Http::assertSent(fn ($request) => $request->url() === 'http://localhost:8001/api/student-info/'.$student->campus_id
            && $request->hasHeader('Origin', 'https://rfid-test.example'));

        $this->assertDatabaseHas('rfid_transactions', [
            'student_id' => $student->id,
            'rfid_code' => $student->rfid_code,
            'campus_id' => $student->campus_id,
            'year_level' => $student->year_level,
        ]);
    }

    public function test_amis_student_without_a_local_record_is_accepted(): void
    {
        Http::fake([
            'http://localhost:8001/api/student-info/200500001' => Http::response([
                'student' => [[
                    'user_id' => 25,
                    'campus_id' => '200500001',
                    'first_name' => 'Maria',
                    'middle_name' => 'Santos',
                    'last_name' => 'Reyes',
                    'academic_program_id' => 'BSCS',
                    'title' => 'BS Computer Science',
                    'classification' => 'Senior',
                    'status' => 'Active',
                ]],
            ]),
        ]);

        $this->withHeader('X-Scanner-Token', $this->scannerToken)
            ->postJson('/api/rfid/scan', ['identifier' => '200500001'])
            ->assertOk()
            ->assertJson([
                'cardholderType' => 'student',
                'campusId' => '200500001',
                'rfidCode' => '—',
                'name' => 'Maria Santos Reyes',
                'firstName' => 'Maria',
                'middleName' => 'Santos',
                'lastName' => 'Reyes',
                'program' => 'BS Computer Science',
                'yearLevel' => 'Senior',
                'status' => 'Active',
                'valid' => true,
                'verificationSource' => 'amis',
                'verificationStatus' => 'verified',
            ]);

        $this->assertDatabaseHas('rfid_transactions', [
            'branch_id' => $this->defaultBranch()->id,
            'student_id' => null,
            'cardholder_type' => 'student',
            'rfid_code' => '200500001',
            'campus_id' => '200500001',
            'cardholder_name' => 'Maria Santos Reyes',
            'program' => 'BS Computer Science',
            'year_level' => 'Senior',
            'status' => 'valid',
        ]);
    }

    public function test_student_missing_from_amis_is_denied_with_our_itc_guidance(): void
    {
        Http::fake([
            'http://localhost:8001/api/student-info/209999999' => Http::response([
                'message' => 'Student not found',
            ], 404),
        ]);

        $this->withHeader('X-Scanner-Token', $this->scannerToken)
            ->postJson('/api/rfid/scan', ['identifier' => '209999999'])
            ->assertOk()
            ->assertJson([
                'cardholderType' => 'student',
                'campusId' => '209999999',
                'status' => 'Student record not found',
                'valid' => false,
                'message' => 'Student record not found in AMIS. Please contact OUR/ITC to validate the student record.',
                'verificationSource' => 'amis',
                'verificationStatus' => 'not_found',
            ]);

        $this->assertDatabaseHas('rfid_transactions', [
            'branch_id' => $this->defaultBranch()->id,
            'student_id' => null,
            'cardholder_type' => 'student',
            'campus_id' => '209999999',
            'status' => 'invalid',
        ]);
    }

    public function test_student_is_denied_safely_when_amis_is_unavailable(): void
    {
        Http::fake([
            'http://localhost:8001/api/student-info/208888888' => Http::response(null, 503),
        ]);

        $this->withHeader('X-Scanner-Token', $this->scannerToken)
            ->postJson('/api/rfid/scan', ['identifier' => '208888888'])
            ->assertOk()
            ->assertJson([
                'cardholderType' => 'student',
                'valid' => false,
                'message' => 'AMIS verification is temporarily unavailable. Please contact OUR/ITC to validate the student record.',
                'verificationSource' => 'amis',
                'verificationStatus' => 'unavailable',
            ]);
    }

    public function test_blank_amis_origin_falls_back_to_the_application_url(): void
    {
        config()->set('services.amis.origin', '');
        config()->set('app.url', 'https://las-fallback.example');

        Http::fake([
            'http://localhost:8001/api/student-info/207777777' => Http::response([
                'message' => 'Student not found',
            ], 404),
        ]);

        $this->withHeader('X-Scanner-Token', $this->scannerToken)
            ->postJson('/api/rfid/scan', ['identifier' => '207777777'])
            ->assertOk()
            ->assertJson([
                'verificationSource' => 'amis',
                'verificationStatus' => 'not_found',
                'valid' => false,
            ]);

        Http::assertSent(fn ($request) => $request->hasHeader('Origin', 'https://las-fallback.example'));
    }

    public function test_non_student_identifiers_do_not_call_amis(): void
    {
        $this->withHeader('X-Scanner-Token', $this->scannerToken)
            ->postJson('/api/rfid/scan', ['identifier' => 'ordinary-rfid-card'])
            ->assertOk()
            ->assertJson([
                'cardholderType' => 'unknown',
                'verificationSource' => 'local',
                'verificationStatus' => 'not_found',
            ]);

        Http::assertNothingSent();
    }

    public function test_employee_number_is_accepted_as_legacy_rfid_code_field(): void
    {
        $employee = Employee::create([
            'employee_number' => 'UPCEBU-EMP-200',
            'rfid_code' => 'employee-rfid-200',
            'name' => 'Employee Number User',
            'is_active' => true,
        ]);

        $this->withHeader('X-Scanner-Token', $this->scannerToken)
            ->postJson('/api/rfid/scan', ['rfid_code' => $employee->employee_number])
            ->assertOk()
            ->assertJson([
                'cardholderType' => 'employee',
                'campusId' => $employee->employee_number,
                'rfidCode' => $employee->rfid_code,
                'valid' => true,
            ]);
    }

    public function test_scanner_token_remains_valid_for_the_installation(): void
    {
        $student = Student::create([
            'campus_id' => '2026-EXPIRE',
            'rfid_code' => 'expiring-rfid',
            'name' => 'Expiring Token Student',
            'is_active' => true,
        ]);

        $this->withHeader('X-Scanner-Token', $this->scannerToken)
            ->postJson('/api/rfid/scan', ['identifier' => $student->rfid_code])
            ->assertOk();

        $this->travel(1)->years();

        $this->withHeader('X-Scanner-Token', $this->scannerToken)
            ->postJson('/api/rfid/scan', ['identifier' => $student->rfid_code])
            ->assertOk();
    }
}
