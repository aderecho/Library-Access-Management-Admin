<?php

namespace App\Http\Controllers\Api;

use App\Events\RfidScanRecorded;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\RfidTransaction;
use App\Models\Student;
use App\Services\AmisStudentVerifier;
use Illuminate\Http\Request;

class RfidScanController extends Controller
{
    private const ENTRY_TIMEZONE = 'Asia/Manila';

    public function store(Request $request, AmisStudentVerifier $amisStudentVerifier)
    {
        $scannerToken = $request->attributes->get('scannerToken');

        $validated = $request->validate([
            'identifier' => ['required_without:rfid_code', 'string', 'max:80'],
            'rfid_code' => ['required_without:identifier', 'string', 'max:80'],
        ]);

        $identifierInput = trim($request->filled('identifier')
            ? $validated['identifier']
            : $validated['rfid_code']);

        $scan = $this->isAmisStudentIdentifier($identifierInput)
            ? $this->resolveAmisStudent($identifierInput, $amisStudentVerifier)
            : $this->resolveLocalCardholder($identifierInput);

        $transaction = RfidTransaction::create([
            'branch_id' => $scannerToken->branch_id,
            'student_id' => $scan['studentId'],
            'employee_id' => $scan['employeeId'],
            'cardholder_type' => $scan['cardholderType'],
            'rfid_code' => $scan['transactionRfidCode'],
            'campus_id' => $scan['campusId'] === '—' ? null : $scan['campusId'],
            'cardholder_name' => $scan['name'],
            'program' => $scan['program'] === '—' ? null : $scan['program'],
            'college_department' => $scan['college'] === '—' ? null : $scan['college'],
            'year_level' => $scan['yearLevel'] === '—' ? null : $scan['yearLevel'],
            'transaction_type' => 'time_in',
            'status' => $scan['valid'] ? 'valid' : 'invalid',
            'message' => $scan['message'],
            'scanned_at' => now(self::ENTRY_TIMEZONE),
        ]);

        RfidScanRecorded::dispatch($transaction);

        return response()->json([
            'cardholderType' => $scan['cardholderType'],
            'campusId' => $scan['campusId'],
            'rfidCode' => $scan['rfidCode'],
            'name' => $scan['name'],
            'firstName' => $scan['firstName'],
            'middleName' => $scan['middleName'],
            'lastName' => $scan['lastName'],
            'suffix' => $scan['suffix'],
            'program' => $scan['program'],
            'college' => $scan['college'],
            'yearLevel' => $scan['yearLevel'],
            'status' => $scan['status'],
            'valid' => $scan['valid'],
            'message' => $scan['message'],
            'verificationSource' => $scan['verificationSource'],
            'verificationStatus' => $scan['verificationStatus'],
            'displayDurationMs' => 10000,
            'branch' => $scannerToken->branch?->only(['id', 'name', 'code']),
        ]);
    }

    private function isAmisStudentIdentifier(string $identifier): bool
    {
        return preg_match('/^20\d/', $identifier) === 1;
    }

    private function resolveAmisStudent(string $campusId, AmisStudentVerifier $verifier): array
    {
        $verification = $verifier->verify($campusId);
        $localStudent = Student::where('campus_id', $campusId)->first();

        if ($verification['status'] !== 'verified') {
            $unavailable = $verification['status'] === 'unavailable';

            return [
                'studentId' => $localStudent?->id,
                'employeeId' => null,
                'cardholderType' => 'student',
                'transactionRfidCode' => $localStudent?->rfid_code ?? $campusId,
                'campusId' => $campusId,
                'rfidCode' => $localStudent?->rfid_code ?? '—',
                'name' => $localStudent?->full_name ?? 'Unverified Student',
                'firstName' => $localStudent?->first_name,
                'middleName' => $localStudent?->middle_name,
                'lastName' => $localStudent?->last_name,
                'suffix' => $localStudent?->suffix,
                'program' => $localStudent?->program ?? '—',
                'college' => $localStudent?->college ?? '—',
                'yearLevel' => $localStudent?->year_level ?? '—',
                'status' => $unavailable ? 'Verification unavailable' : 'Student record not found',
                'valid' => false,
                'message' => $unavailable
                    ? 'AMIS verification is temporarily unavailable. Please contact OUR/ITC to validate the student record.'
                    : 'Student record not found in AMIS. Please contact OUR/ITC to validate the student record.',
                'verificationSource' => 'amis',
                'verificationStatus' => $verification['status'],
            ];
        }

        $record = $verification['student'];
        $firstName = $record['first_name'] ?? $localStudent?->first_name;
        $middleName = $record['middle_name'] ?? $localStudent?->middle_name;
        $lastName = $record['last_name'] ?? $localStudent?->last_name;
        $suffix = $record['suffix'] ?? $localStudent?->suffix;
        $name = $this->formatName($firstName, $middleName, $lastName, $suffix)
            ?: ($localStudent?->full_name ?: 'Verified Student');

        return [
            'studentId' => $localStudent?->id,
            'employeeId' => null,
            'cardholderType' => 'student',
            'transactionRfidCode' => $localStudent?->rfid_code ?? $campusId,
            'campusId' => $campusId,
            'rfidCode' => $localStudent?->rfid_code ?? '—',
            'name' => $name,
            'firstName' => $firstName,
            'middleName' => $middleName,
            'lastName' => $lastName,
            'suffix' => $suffix,
            'program' => $record['title'] ?? $record['academic_program_id'] ?? $localStudent?->program ?? '—',
            'college' => $record['college'] ?? $localStudent?->college ?? '—',
            'yearLevel' => $record['classification'] ?? $localStudent?->year_level ?? '—',
            'status' => $record['status'] ?? 'Verified by AMIS',
            'valid' => true,
            'message' => 'Student verified through AMIS. Library entry recorded successfully.',
            'verificationSource' => 'amis',
            'verificationStatus' => 'verified',
        ];
    }

    private function resolveLocalCardholder(string $identifierInput): array
    {
        $student = Student::where('rfid_code', $identifierInput)->first();
        $employee = $student ? null : Employee::where('rfid_code', $identifierInput)->first();
        $student ??= $employee ? null : Student::where('campus_id', $identifierInput)->first();
        $employee ??= $student ? null : Employee::where('employee_number', $identifierInput)->first();
        $cardholder = $student ?? $employee;
        $valid = (bool) ($cardholder?->is_active);

        return [
            'studentId' => $student?->id,
            'employeeId' => $employee?->id,
            'cardholderType' => $student ? 'student' : ($employee ? 'employee' : 'unknown'),
            'transactionRfidCode' => $cardholder?->rfid_code ?? $identifierInput,
            'campusId' => $student?->campus_id ?? $employee?->employee_number ?? '—',
            'rfidCode' => $cardholder?->rfid_code ?? '—',
            'name' => $cardholder?->full_name ?? 'Unregistered Card',
            'firstName' => $cardholder?->first_name,
            'middleName' => $cardholder?->middle_name,
            'lastName' => $cardholder?->last_name,
            'suffix' => $cardholder?->suffix,
            'program' => $student?->program ?? $employee?->position ?? 'No cardholder record found',
            'college' => $student?->college ?? $employee?->office ?? '—',
            'yearLevel' => $student?->year_level ?? ($employee ? 'Employee' : '—'),
            'status' => $cardholder?->status ?? 'Unknown Card',
            'valid' => $valid,
            'message' => $valid
                ? 'Library entry recorded successfully.'
                : ($cardholder ? 'Access denied. Please proceed to the help desk.' : 'This RFID card is not registered. Please proceed to the help desk.'),
            'verificationSource' => 'local',
            'verificationStatus' => $cardholder ? 'matched' : 'not_found',
        ];
    }

    private function formatName(?string $firstName, ?string $middleName, ?string $lastName, ?string $suffix): string
    {
        return collect([$firstName, $middleName, $lastName, $suffix])
            ->filter(fn ($part) => filled($part))
            ->map(fn ($part) => trim((string) $part))
            ->implode(' ');
    }
}
