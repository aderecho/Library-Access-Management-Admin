<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\RfidChangeLog;
use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RfidDirectoryController extends Controller
{
    public function index(Request $request)
    {
        $students = DB::table('students')->select([
            'id',
            DB::raw("'student' as cardholder_type"),
            DB::raw('campus_id as identifier'),
            'rfid_code',
            'first_name',
            'middle_name',
            'last_name',
            'suffix',
            DB::raw('program as primary_detail'),
            DB::raw('college as secondary_detail'),
            'status',
            'is_active',
        ]);

        $employees = DB::table('employees')->select([
            'id',
            DB::raw("'employee' as cardholder_type"),
            DB::raw('employee_number as identifier'),
            'rfid_code',
            'first_name',
            'middle_name',
            'last_name',
            'suffix',
            DB::raw('position as primary_detail'),
            DB::raw('office as secondary_detail'),
            'status',
            'is_active',
        ]);

        $search = trim($request->string('search')->toString());
        $type = $request->string('type')->toString();
        $activeTab = $request->string('tab')->toString() === 'changes' ? 'changes' : 'directory';

        $directory = DB::query()
            ->fromSub($students->unionAll($employees), 'rfid_directory')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('identifier', 'like', "%{$search}%")
                        ->orWhere('rfid_code', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('suffix', 'like', "%{$search}%");
                });
            })
            ->when(in_array($type, ['student', 'employee'], true), fn ($query) => $query->where('cardholder_type', $type))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(25)
            ->withQueryString();

        $directory->getCollection()->transform(function ($record) {
            $record->full_name = collect([
                $record->first_name,
                $record->middle_name,
                $record->last_name,
                $record->suffix,
            ])->filter(fn ($part) => filled($part))->implode(' ');

            return $record;
        });

        $recentChanges = RfidChangeLog::with('changedBy')
            ->latest('created_at')
            ->paginate(25, ['*'], 'changes_page')
            ->withQueryString();

        return view('admin.rfid-directory.index', compact('directory', 'recentChanges', 'activeTab'));
    }

    public function update(Request $request, string $cardholderType, int $cardholderId)
    {
        $validated = $request->validate([
            'rfid_code' => ['required', 'string', 'max:255'],
        ]);
        $newRfidCode = trim($validated['rfid_code']);

        if ($newRfidCode === '') {
            throw ValidationException::withMessages(['rfid_code' => 'The RFID code is required.']);
        }

        DB::transaction(function () use ($request, $cardholderType, $cardholderId, $newRfidCode): void {
            $cardholder = $this->cardholderQuery($cardholderType)->lockForUpdate()->findOrFail($cardholderId);
            $oldRfidCode = (string) $cardholder->rfid_code;

            if ($oldRfidCode === $newRfidCode) {
                throw ValidationException::withMessages(['rfid_code' => 'Enter a different RFID code.']);
            }

            $duplicateStudent = Student::where('rfid_code', $newRfidCode)
                ->when($cardholderType === 'student', fn ($query) => $query->whereKeyNot($cardholderId))
                ->exists();
            $duplicateEmployee = Employee::where('rfid_code', $newRfidCode)
                ->when($cardholderType === 'employee', fn ($query) => $query->whereKeyNot($cardholderId))
                ->exists();

            if ($duplicateStudent || $duplicateEmployee) {
                throw ValidationException::withMessages(['rfid_code' => 'This RFID code is already assigned to another cardholder.']);
            }

            $cardholder->update(['rfid_code' => $newRfidCode]);

            RfidChangeLog::create([
                'cardholder_type' => $cardholderType,
                'cardholder_id' => $cardholder->getKey(),
                'cardholder_identifier' => $cardholderType === 'student' ? $cardholder->campus_id : $cardholder->employee_number,
                'cardholder_name' => $cardholder->full_name,
                'old_rfid_code' => $oldRfidCode,
                'new_rfid_code' => $newRfidCode,
                'changed_by' => $request->user()->id,
            ]);
        });

        return redirect()->route('admin.rfid-directory.index')->with('success', 'RFID record updated and logged.');
    }

    private function cardholderQuery(string $cardholderType): Model
    {
        abort_unless(in_array($cardholderType, ['student', 'employee'], true), 404);

        return $cardholderType === 'student' ? new Student : new Employee;
    }
}
