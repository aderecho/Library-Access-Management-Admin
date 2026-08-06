<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\RfidTransaction;
use App\Models\Role;
use App\Models\Student;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $defaultBranch = Branch::firstOrCreate(['code' => 'MAIN'], ['name' => 'Main Library', 'is_active' => true]);
        Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'description' => 'Full access, including user accounts and roles.']
        );

        Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'description' => 'Access to dashboard, transactions, and reports.']
        );

        Role::firstOrCreate(
            ['slug' => 'itc-tech'],
            ['name' => 'ITC-Tech', 'description' => 'ITC technical support role with branch configuration access.']
        );

        Role::firstOrCreate(
            ['slug' => 'report-viewer'],
            ['name' => 'Report Viewer', 'description' => 'Reserved for read-only report access.']
        );

        $this->call([
            UserSeeder::class,
            StudentSeeder::class,
            EmployeeSeeder::class,
            CardholderPhotoSeeder::class,
        ]);

        if (RfidTransaction::count() === 0) {
            $activeStudents = Student::where('is_active', true)->get();
            $activeEmployees = Employee::where('is_active', true)->get();

            foreach (range(0, 25) as $index) {
                $isEmployeeScan = $index % 5 === 0;
                $cardholder = $isEmployeeScan
                    ? $activeEmployees[$index % $activeEmployees->count()]
                    : $activeStudents[$index % $activeStudents->count()];

                RfidTransaction::create([
                    'branch_id' => $defaultBranch->id,
                    'student_id' => $isEmployeeScan ? null : $cardholder->id,
                    'employee_id' => $isEmployeeScan ? $cardholder->id : null,
                    'rfid_code' => $cardholder->rfid_code,
                    'campus_id' => $isEmployeeScan ? $cardholder->employee_number : $cardholder->campus_id,
                    'cardholder_type' => $isEmployeeScan ? 'employee' : 'student',
                    'cardholder_name' => $cardholder->full_name,
                    'program' => $isEmployeeScan ? $cardholder->position : $cardholder->program,
                    'college_department' => $isEmployeeScan ? $cardholder->office : $cardholder->college,
                    'year_level' => $isEmployeeScan ? null : $cardholder->year_level,
                    'transaction_type' => 'time_in',
                    'status' => 'valid',
                    'message' => 'Library entry recorded successfully.',
                    'scanned_at' => now()->subDays($index % 7)->subMinutes(($index + 1) * 11),
                ]);
            }

            RfidTransaction::create([
                'branch_id' => $defaultBranch->id,
                'rfid_code' => '0000000000',
                'cardholder_type' => 'unknown',
                'cardholder_name' => 'Unregistered Card',
                'transaction_type' => 'time_in',
                'status' => 'invalid',
                'message' => 'This RFID card is not registered.',
                'scanned_at' => now()->subMinutes(5),
            ]);
        }

        if (! RfidTransaction::whereNotNull('employee_id')->exists()) {
            Employee::where('is_active', true)->get()->each(function (Employee $employee, int $index) use ($defaultBranch) {
                foreach (range(1, $index + 1) as $scan) {
                    RfidTransaction::create([
                        'branch_id' => $defaultBranch->id,
                        'employee_id' => $employee->id,
                        'rfid_code' => $employee->rfid_code,
                        'campus_id' => $employee->employee_number,
                        'cardholder_type' => 'employee',
                        'cardholder_name' => $employee->full_name,
                        'program' => $employee->position,
                        'college_department' => $employee->office,
                        'transaction_type' => 'time_in',
                        'status' => 'valid',
                        'message' => 'Library entry recorded successfully.',
                        'scanned_at' => now()->subMinutes(($index + 1) * $scan * 13),
                    ]);
                }
            });
        }
    }
}
