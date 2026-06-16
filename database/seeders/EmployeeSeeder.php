<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            ['employee_number' => 'UPCEBU-EMP-001', 'rfid_code' => '0553827111', 'name' => 'Roberto Garcia', 'position' => 'Administrative Officer', 'office' => 'Office of the Chancellor', 'status' => 'Active Employee', 'is_active' => true],
            ['employee_number' => 'UPCEBU-EMP-002', 'rfid_code' => '0553827222', 'name' => 'Elena Ramos', 'position' => 'University Librarian', 'office' => 'University Library', 'status' => 'Active Employee', 'is_active' => true],
            ['employee_number' => 'UPCEBU-EMP-003', 'rfid_code' => '0553827333', 'name' => 'Daniel Flores', 'position' => 'Faculty Member', 'office' => 'College of Science', 'status' => 'Active Employee', 'is_active' => true],
            ['employee_number' => 'UPCEBU-EMP-004', 'rfid_code' => '0553827444', 'name' => 'Teresa Mendoza', 'position' => 'Administrative Assistant', 'office' => 'Human Resources Office', 'status' => 'Inactive Employee', 'is_active' => false],
        ];

        foreach ($employees as $employee) {
            Employee::updateOrCreate(
                ['employee_number' => $employee['employee_number']],
                $employee
            );
        }
    }
}
