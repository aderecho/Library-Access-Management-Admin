<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $students = [
            ['campus_id' => '2024-10001', 'rfid_code' => '0453827211', 'name' => 'Juan Dela Cruz', 'program' => 'BS Computer Science', 'college' => 'College of Science', 'year_level' => '3rd Year', 'status' => 'Active Student', 'is_active' => true],
            ['campus_id' => '2024-10002', 'rfid_code' => '0453827222', 'name' => 'Maria Santos', 'program' => 'BA Communication', 'college' => 'College of Communication, Art, and Design', 'year_level' => '2nd Year', 'status' => 'Active Student', 'is_active' => true],
            ['campus_id' => '2024-10003', 'rfid_code' => '0453827333', 'name' => 'Carlos Reyes', 'program' => 'BS Management', 'college' => 'School of Management', 'year_level' => '4th Year', 'status' => 'Active Student', 'is_active' => true],
            ['campus_id' => '2024-10004', 'rfid_code' => '0453827444', 'name' => 'Ana Cruz', 'program' => 'BS Biology', 'college' => 'College of Science', 'year_level' => '1st Year', 'status' => 'Active Student', 'is_active' => true],
            ['campus_id' => '2024-10005', 'rfid_code' => '0453827555', 'name' => 'Mark Villanueva', 'program' => 'BA Psychology', 'college' => 'College of Social Sciences', 'year_level' => '3rd Year', 'status' => 'Inactive Student', 'is_active' => false],
        ];

        foreach ($students as $student) {
            Student::updateOrCreate(
                ['campus_id' => $student['campus_id']],
                $student
            );
        }
    }
}
