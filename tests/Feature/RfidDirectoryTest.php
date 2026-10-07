<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\RfidChangeLog;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
            ->assertSee('Edit')
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
            ->assertSessionHas('success', 'Cardholder record updated successfully.');

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
            ->assertDontSee('>Edit<', false);

        $this->actingAs($user)
            ->put(route('admin.rfid-directory.update', ['student', $student->id]), ['rfid_code' => 'FORBIDDEN'])
            ->assertForbidden();

        $this->assertSame('VIEW-ONLY-RFID', $student->fresh()->rfid_code);
    }

    public function test_student_and_employee_profiles_can_be_edited_without_changing_rfid(): void
    {
        $user = $this->directoryUser();

        foreach (['student', 'employee'] as $type) {
            $model = $type === 'student' ? Student::class : Employee::class;
            $identifier = $type === 'student' ? 'campus_id' : 'employee_number';
            $primary = $type === 'student' ? 'program' : 'position';
            $secondary = $type === 'student' ? 'college' : 'office';
            $record = $model::create([
                $identifier => 'OLD-'.$type,
                'rfid_code' => 'RFID-'.$type,
                'name' => 'Original Person',
                'is_active' => true,
            ]);
            $payload = [
                $identifier => 'NEW-'.$type,
                'rfid_code' => $record->rfid_code,
                'first_name' => 'Updated',
                'middle_name' => 'Middle',
                'last_name' => 'Person',
                'suffix' => 'Jr.',
                $primary => 'Updated primary detail',
                $secondary => 'Updated secondary detail',
                'status' => 'Inactive',
                'is_active' => '0',
            ];
            if ($type === 'student') {
                $payload['year_level'] = 'Third Year';
            }

            $this->actingAs($user)->get(route('admin.rfid-directory.index'))
                ->assertOk()->assertSee('Edit Cardholder')->assertSee('name="'.$identifier.'"', false);
            $this->put(route('admin.rfid-directory.update', [$type, $record->id]), $payload)
                ->assertRedirect(route('admin.rfid-directory.index'))->assertSessionHasNoErrors();
            $record->refresh();
            foreach ($payload as $field => $value) {
                if ($field !== 'is_active') {
                    $this->assertSame($value, $record->{$field});
                }
            }
            $this->assertFalse($record->is_active);
            $this->assertSame('Updated Middle Person Jr.', $record->full_name);
        }
        $this->assertDatabaseCount('rfid_change_logs', 0);
    }

    public function test_duplicate_identifier_rejects_the_entire_profile_update(): void
    {
        $user = $this->directoryUser();
        $student = Student::create(['campus_id' => 'ORIGINAL', 'rfid_code' => 'ORIGINAL-RFID', 'name' => 'Original Person']);
        Student::create(['campus_id' => 'TAKEN', 'rfid_code' => 'TAKEN-RFID', 'name' => 'Other Person']);

        $this->actingAs($user)->put(route('admin.rfid-directory.update', ['student', $student->id]), [
            'campus_id' => 'TAKEN', 'rfid_code' => 'NEW-RFID', 'first_name' => 'Changed',
        ])->assertSessionHasErrors('campus_id');
        $this->assertSame('ORIGINAL', $student->fresh()->campus_id);
        $this->assertSame('ORIGINAL-RFID', $student->fresh()->rfid_code);
        $this->assertDatabaseCount('rfid_change_logs', 0);
    }

    public function test_campus_id_spaces_are_removed_before_display_and_unique_validation(): void
    {
        $student = Student::create(['campus_id' => '2019 06270', 'rfid_code' => 'SPACE-RFID', 'name' => 'Test Student']);
        $this->actingAs($this->directoryUser())->get(route('admin.rfid-directory.index'))
            ->assertOk()->assertSee('201906270')->assertDontSee('2019 06270');
        $this->put(route('admin.rfid-directory.update', ['student', $student->id]), [
            'campus_id' => ' 2019 06270 ', 'rfid_code' => 'SPACE-RFID',
        ])->assertSessionHasNoErrors();
        $this->assertSame('201906270', $student->fresh()->campus_id);

        Student::create(['campus_id' => '202600001', 'rfid_code' => 'OTHER-RFID', 'name' => 'Other Student']);
        $this->put(route('admin.rfid-directory.update', ['student', $student->id]), [
            'campus_id' => '2026 00001', 'rfid_code' => 'SPACE-RFID',
        ])->assertSessionHasErrors('campus_id');
        $this->assertSame('201906270', $student->fresh()->campus_id);
    }

    public function test_directory_search_ignores_case_for_students_and_employees(): void
    {
        // Reproduce case-sensitive LIKE instead of relying on SQLite's default.
        DB::statement('PRAGMA case_sensitive_like = ON');
        $student = Student::create([
            'campus_id' => 'STUDENT-CASE', 'rfid_code' => 'STUDENT-RFID-CASE',
            'first_name' => 'GODFREY', 'middle_name' => 'ALPHA', 'last_name' => 'CEBU', 'suffix' => 'JR.',
        ]);
        $employee = Employee::create([
            'employee_number' => 'EMPLOYEE-CASE', 'rfid_code' => 'EMPLOYEE-RFID-CASE',
            'first_name' => 'Godfrey', 'last_name' => 'Faculty',
        ]);
        Student::create(['campus_id' => 'OTHER', 'rfid_code' => 'OTHER-RFID', 'name' => 'Unrelated Person']);
        $this->actingAs($this->directoryUser());

        try {
            foreach (['godfrey', 'GODFREY', 'GoDfReY'] as $search) {
                $response = $this->get(route('admin.rfid-directory.index', ['search' => $search]))->assertOk();
                $this->assertSame(2, $response->viewData('directory')->total());
                $response->assertSee($student->campus_id)->assertSee($employee->employee_number)->assertDontSee('Unrelated Person');
            }
            foreach (['student-case', 'student-rfid-case', 'alpha', 'cebu', 'jr.'] as $search) {
                $response = $this->get(route('admin.rfid-directory.index', ['search' => $search]))->assertOk();
                $this->assertSame(1, $response->viewData('directory')->total());
                $response->assertSee($student->campus_id);
            }
            $response = $this->get(route('admin.rfid-directory.index', ['search' => 'godfrey', 'type' => 'employee']))->assertOk();
            $this->assertSame(1, $response->viewData('directory')->total());
            $response->assertSee($employee->employee_number)->assertDontSee($student->campus_id);
        } finally {
            DB::statement('PRAGMA case_sensitive_like = OFF');
        }
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
