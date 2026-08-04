<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RfidTransaction;
use App\Models\Branch;
use App\Services\RfidTransactionReadModel;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function monitor(Request $request)
    {
        $user = $request->user();
        $branches = $user->role?->slug === 'super-admin'
            ? Branch::where('is_active', true)->orderBy('name')->get()
            : collect([$user->branch])->filter();
        $branchId = $user->role?->slug === 'super-admin'
            ? ($request->integer('branch_id') ?: $branches->first()?->id)
            : $user->branch_id;

        abort_if(! $branchId || ! $user->canAccessBranch($branchId), 403, 'A valid branch assignment is required to use the entry monitor.');

        $recent = RfidTransaction::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->with(['branch', 'student.primaryPhoto', 'employee.primaryPhoto'])
            ->latest('scanned_at')
            ->limit(4)
            ->get();
        $latest = $recent->first();
        $branch = $branches->firstWhere('id', $branchId) ?? Branch::findOrFail($branchId);

        return view('admin.entry-monitor', compact('latest', 'recent', 'branch', 'branches'));
    }

    public function index(Request $request, RfidTransactionReadModel $readModel)
    {
        $user = $request->user();
        $branches = $user->role?->slug === 'super-admin'
            ? Branch::where('is_active', true)->orderBy('name')->get()
            : collect([$user->branch])->filter();
        $branchId = $user->role?->slug === 'super-admin'
            ? ($request->filled('branch_id') ? $request->integer('branch_id') : null)
            : $user->branch_id;
        abort_if($user->role?->slug !== 'super-admin' && (! $branchId || ! $user->branch?->is_active), 403, 'An active branch assignment is required.');
        abort_if($user->role?->slug === 'super-admin' && $branchId && ! $branches->contains('id', $branchId), 403, 'The selected branch is not available.');

        $transactions = $readModel->paginate([
            'branch_id' => $branchId,
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'from' => $request->string('from')->toString(),
            'to' => $request->string('to')->toString(),
        ])->withQueryString();

        return view('admin.transactions.index', compact('transactions', 'branches', 'branchId'));
    }
}
