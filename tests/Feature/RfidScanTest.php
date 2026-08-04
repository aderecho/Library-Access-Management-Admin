<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\ScannerToken;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfidScanTest extends TestCase
{
    use RefreshDatabase;

    private string $scannerToken;

    protected function setUp(): void
    {
        parent::setUp();

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
            'name' => 'Active Employee',
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
                'name' => $employee->name,
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
            'cardholder_name' => $employee->name,
            'campus_id' => $employee->employee_number,
            'status' => 'valid',
        ]);
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

        $this->withHeader('X-Scanner-Token', $this->scannerToken)
            ->postJson('/api/rfid/scan', ['identifier' => $student->campus_id])
            ->assertOk()
            ->assertJson([
                'cardholderType' => 'student',
                'campusId' => $student->campus_id,
                'rfidCode' => $student->rfid_code,
                'yearLevel' => $student->year_level,
                'valid' => true,
            ]);

        $this->assertDatabaseHas('rfid_transactions', [
            'student_id' => $student->id,
            'rfid_code' => $student->rfid_code,
            'campus_id' => $student->campus_id,
            'year_level' => $student->year_level,
        ]);
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
