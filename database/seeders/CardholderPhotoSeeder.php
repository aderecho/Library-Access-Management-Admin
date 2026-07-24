<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Student;
use Illuminate\Database\Seeder;

class CardholderPhotoSeeder extends Seeder
{
    public function run(): void
    {
        $students = [
            '202410001' => 'images/cardholders/juan-dela-cruz.png',
            '202410002' => 'images/cardholders/maria-santos.png',
            '202410003' => 'images/cardholders/carlos-reyes.png',
            '202410004' => 'images/cardholders/ana-cruz.png',
            '202410005' => 'images/cardholders/mark-villanueva.png',
        ];

        foreach ($students as $campusId => $path) {
            Student::where('campus_id', $campusId)->first()?->photos()->updateOrCreate(
                ['is_primary' => true],
                ['path' => $path]
            );
        }

        $employees = [
            'UPCEBU-EMP-001' => 'images/cardholders/roberto-garcia.png',
            'UPCEBU-EMP-002' => 'images/cardholders/elena-ramos.png',
            'UPCEBU-EMP-003' => 'images/cardholders/daniel-flores.png',
            'UPCEBU-EMP-004' => 'images/cardholders/teresa-mendoza.png',
        ];

        foreach ($employees as $employeeNumber => $path) {
            Employee::where('employee_number', $employeeNumber)->first()?->photos()->updateOrCreate(
                ['is_primary' => true],
                ['path' => $path]
            );
        }
    }
}
