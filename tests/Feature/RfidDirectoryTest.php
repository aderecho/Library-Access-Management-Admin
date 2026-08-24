<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\RfidChangeLog;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfidDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_creates_rfid_directory_role_with_view_and_update_permissions(): void
    {
        $role = Role::where('slug', 'rfid-directory')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            ['rfid-directory.view', 'rfid-directory.update'],
            $role->permissions
        );
    }

    public function test_rfid_directory_role_can_view_student_and_employee_records(): void
    {
        $user = $this->directoryUser();
        $student = Student::create([
            'campus_id' => '2026-12345',
            'rfid_code' => 'STUDENT-RFID-1',
            'name' => 'Maria Cebu',
            'program' => 'BS Computer Science',
            'college' => 'College of Science',
            'is_active' => true,
        ]);
        $employee = Employee::create([
            'employee_number' => 'EMP-1001',
            'rfid_code' => 'EMPLOYEE-RFID-1',
            'name' => 'Juan Faculty',
            'position' => 'Professor',
            'office' => 'Computer Science',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('admin.rfid-directory.index'))
            ->assertOk()
            ->assertSee('RFID Directory')
            ->assertSee($student->campus_id)
            ->assertSee($student->rfid_code)
            ->assertSee($employee->employee_number)
            ->assertSee($employee->rfid_code)
            ->assertSee('Update')
            ->assertSee('Recent RFID Changes')
            ->assertDontSee('No RFID changes have been recorded yet.')
            ->assertSee('popover="auto"', false)
            ->assertSee('popovertarget="rfid-update-student-'.$student->id.'"', false)
            ->assertDontSee('rfid-update-backdrop', false);

        $this->actingAs($user)
            ->get(route('admin.rfid-directory.index', ['tab' => 'changes']))
            ->assertOk()
            ->assertSee('Recent RFID Changes')
            ->assertSee('No RFID changes have been recorded yet.')
            ->assertDontSee($student->rfid_code)
            ->assertDontSee('Search name, ID, or RFID');

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.rfid-directory.index'));
    }

    public function test_updating_an_rfid_records_old_and_new_values_with_the_user(): void
    {
        $user = $this->directoryUser();
        $student = Student::create([
            'campus_id' => '2026-22222',
            'rfid_code' => 'OLD-RFID',
            'name' => 'Audit Student',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->put(route('admin.rfid-directory.update', ['student', $student->id]), [
                'rfid_code' => 'NEW-RFID',
            ])
            ->assertRedirect(route('admin.rfid-directory.index'))
            ->assertSessionHas('success', 'RFID record updated and logged.');

        $this->assertSame('NEW-RFID', $student->fresh()->rfid_code);
        $this->assertDatabaseHas('rfid_change_logs', [
            'cardholder_type' => 'student',
            'cardholder_id' => $student->id,
            'cardholder_identifier' => $student->campus_id,
            'old_rfid_code' => 'OLD-RFID',
            'new_rfid_code' => 'NEW-RFID',
            'changed_by' => $user->id,
        ]);

        $log = RfidChangeLog::firstOrFail();
        $this->assertSame('Audit Student', $log->cardholder_name);

        $this->actingAs($user)
            ->get(route('admin.rfid-directory.index', ['tab' => 'changes']))
            ->assertOk()
            ->assertSee('Audit Student')
            ->assertSee('OLD-RFID')
            ->assertSee('NEW-RFID')
            ->assertDontSee('Search name, ID, or RFID');
    }

    public function test_rfid_code_cannot_be_assigned_to_a_different_student_or_employee(): void
    {
        $user = $this->directoryUser();
        $student = Student::create([
            'campus_id' => '2026-33333',
            'rfid_code' => 'STUDENT-OLD',
            'name' => 'Duplicate Check',
            'is_active' => true,
        ]);
        Employee::create([
            'employee_number' => 'EMP-3333',
            'rfid_code' => 'TAKEN-RFID',
            'name' => 'Existing Employee',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->from(route('admin.rfid-directory.index'))
            ->put(route('admin.rfid-directory.update', ['student', $student->id]), [
                'rfid_code' => 'TAKEN-RFID',
            ])
            ->assertRedirect(route('admin.rfid-directory.index'))
            ->assertSessionHasErrors('rfid_code');

        $this->assertSame('STUDENT-OLD', $student->fresh()->rfid_code);
        $this->assertDatabaseCount('rfid_change_logs', 0);
    }

    public function test_view_only_permission_cannot_update_rfid_records(): void
    {
        $role = Role::create([
            'name' => 'RFID Viewer',
            'slug' => 'rfid-viewer',
            'permissions' => ['rfid-directory.view'],
        ]);
        $user = User::create([
            'name' => 'RFID Viewer',
            'email' => 'rfid-viewer@example.com',
            'password' => 'password123',
            'role_id' => $role->id,
            'is_active' => true,
        ]);
        $student = Student::create([
            'campus_id' => '2026-44444',
            'rfid_code' => 'VIEW-ONLY-RFID',
            'name' => 'View Only Student',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('admin.rfid-directory.index'))
            ->assertOk()
            ->assertDontSee('>Update<', false);

        $this->actingAs($user)
            ->put(route('admin.rfid-directory.update', ['student', $student->id]), ['rfid_code' => 'FORBIDDEN'])
            ->assertForbidden();

        $this->assertSame('VIEW-ONLY-RFID', $student->fresh()->rfid_code);
    }

    private function directoryUser(): User
    {
        $role = Role::where('slug', 'rfid-directory')->firstOrFail();

        return User::create([
            'name' => 'RFID Directory Staff',
            'email' => 'rfid-directory@example.com',
            'password' => 'password123',
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }
}
