<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\StudentSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PersonNameFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_person_tables_use_structured_name_columns(): void
    {
        foreach (['users', 'students', 'employees'] as $table) {
            $this->assertTrue(Schema::hasColumns($table, [
                'first_name',
                'middle_name',
                'last_name',
                'suffix',
            ]));
            $this->assertFalse(Schema::hasColumn($table, 'name'));
        }
    }

    public function test_person_models_build_a_consistent_full_name(): void
    {
        $attributes = [
            'first_name' => 'Maria',
            'middle_name' => 'Isabel',
            'last_name' => 'Santos',
            'suffix' => 'Jr.',
        ];

        $user = new User($attributes);
        $student = new Student($attributes);
        $employee = new Employee($attributes);

        foreach ([$user, $student, $employee] as $person) {
            $this->assertSame('Maria Isabel Santos Jr.', $person->full_name);
            $this->assertSame($person->full_name, $person->name);
        }
    }

    public function test_legacy_name_input_is_split_without_losing_the_display_name(): void
    {
        $student = new Student(['name' => 'Juan Dela Cruz III']);

        $this->assertSame('Juan', $student->first_name);
        $this->assertNull($student->middle_name);
        $this->assertSame('Dela Cruz', $student->last_name);
        $this->assertSame('III', $student->suffix);
        $this->assertSame('Juan Dela Cruz III', $student->full_name);
    }

    public function test_person_seeders_populate_structured_names(): void
    {
        $this->seed([
            StudentSeeder::class,
            EmployeeSeeder::class,
            UserSeeder::class,
        ]);

        $this->assertDatabaseHas('students', [
            'campus_id' => '201955147',
            'first_name' => 'DONALD',
            'last_name' => 'LABIAL',
        ]);
        $this->assertDatabaseHas('employees', [
            'employee_number' => '100029267',
            'first_name' => 'AERIEL',
            'last_name' => 'CABALLERO',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'admin@upcebu.edu.ph',
            'first_name' => 'UP Cebu',
            'last_name' => 'Super Admin',
        ]);
    }
}
