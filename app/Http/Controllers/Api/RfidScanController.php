<?php

namespace App\Http\Controllers\Api;

use App\Events\RfidScanRecorded;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\RfidTransaction;
use App\Models\Student;
use Illuminate\Http\Request;

class RfidScanController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'identifier' => ['required_without:rfid_code', 'string', 'max:80'],
            'rfid_code' => ['required_without:identifier', 'string', 'max:80'],
        ]);

        $identifierInput = trim($request->filled('identifier')
            ? $validated['identifier']
            : $validated['rfid_code']);

        $student = Student::where('rfid_code', $identifierInput)->first();
        $employee = $student ? null : Employee::where('rfid_code', $identifierInput)->first();
        $student ??= $employee ? null : Student::where('campus_id', $identifierInput)->first();
        $employee ??= $student ? null : Employee::where('employee_number', $identifierInput)->first();

        $cardholder = $student ?? $employee;
        $cardholderType = $student ? 'student' : ($employee ? 'employee' : 'unknown');
        $identifier = $student?->campus_id ?? $employee?->employee_number;
        $rfidCode = $cardholder?->rfid_code ?? $identifierInput;
        $valid = (bool) ($cardholder?->is_active);

        $message = $valid
            ? 'Library entry recorded successfully.'
            : ($cardholder ? 'Access denied. Please proceed to the help desk.' : 'This RFID card is not registered. Please proceed to the help desk.');

        $transaction = RfidTransaction::create([
            'student_id' => $student?->id,
            'employee_id' => $employee?->id,
            'cardholder_type' => $cardholderType,
            'rfid_code' => $rfidCode,
            'campus_id' => $identifier,
            'cardholder_name' => $cardholder?->name ?? 'Unregistered Card',
            'program' => $student?->program ?? $employee?->position,
            'college_department' => $student?->college ?? $employee?->office,
            'transaction_type' => 'time_in',
            'status' => $valid ? 'valid' : 'invalid',
            'message' => $message,
            'scanned_at' => now(),
        ]);

        RfidScanRecorded::dispatch($transaction);

        return response()->json([
            'cardholderType' => $cardholderType,
            'campusId' => $identifier ?? '—',
            'rfidCode' => $cardholder?->rfid_code ?? '—',
            'name' => $cardholder?->name ?? 'Unregistered Card',
            'program' => $student?->program ?? $employee?->position ?? 'No cardholder record found',
            'college' => $student?->college ?? $employee?->office ?? '—',
            'yearLevel' => $student?->year_level ?? ($employee ? 'Employee' : '—'),
            'status' => $cardholder?->status ?? 'Unknown Card',
            'valid' => $valid,
            'message' => $message,
            'displayDurationMs' => 10000,
        ]);
    }
}
