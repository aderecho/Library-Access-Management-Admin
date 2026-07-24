<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RfidTransaction;
use App\Services\RfidTransactionReadModel;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function monitor()
    {
        $recent = RfidTransaction::query()
            ->with(['student.primaryPhoto', 'employee.primaryPhoto'])
            ->latest('scanned_at')
            ->limit(4)
            ->get();
        $latest = $recent->first();

        return view('admin.entry-monitor', compact('latest', 'recent'));
    }

    public function index(Request $request, RfidTransactionReadModel $readModel)
    {
        $transactions = $readModel->paginate([
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'from' => $request->string('from')->toString(),
            'to' => $request->string('to')->toString(),
        ])->withQueryString();

        return view('admin.transactions.index', compact('transactions'));
    }
}
