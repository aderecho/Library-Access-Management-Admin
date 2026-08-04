<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::withCount(['scannerTokens', 'users'])->orderBy('name')->get();

        return view('admin.branches.index', compact('branches'));
    }

    public function store(Request $request)
    {
        Branch::create($request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:branches,code'],
        ]) + ['is_active' => true]);

        return back()->with('success', 'Branch created. You can now assign scanners and monitor users to it.');
    }

    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('branches', 'code')->ignore($branch->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        $branch->update($validated);

        return back()->with('success', 'Branch updated.');
    }
}
