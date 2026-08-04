<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScannerToken;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ScannerTokenController extends Controller
{
    public function index()
    {
        $scannerTokens = ScannerToken::with('branch')->latest()->paginate(15);
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('admin.scanner-tokens.index', compact('scannerTokens', 'branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'branch_id' => ['required', 'exists:branches,id'],
            'device_id' => ['nullable', 'string', 'max:150', 'unique:scanner_tokens,device_id'],
        ]);

        $token = ScannerToken::generateToken();
        $scannerToken = ScannerToken::create([
            ...$validated,
            'token_hash' => hash('sha256', $token),
            'token_prefix' => substr($token, 0, 22),
            'is_active' => true,
        ]);

        return redirect()->route('admin.scanner-tokens.index')
            ->with('success', "Scanner registration created for {$scannerToken->name}.")
            ->with('generated_scanner_token', $token);
    }

    public function update(Request $request, ScannerToken $scannerToken)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'branch_id' => ['required', 'exists:branches,id'],
            'device_id' => ['nullable', 'string', 'max:150', Rule::unique('scanner_tokens', 'device_id')->ignore($scannerToken->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $scannerToken->update($validated);

        return redirect()->route('admin.scanner-tokens.index')->with('success', 'Scanner registration updated.');
    }

    public function regenerate(ScannerToken $scannerToken)
    {
        $token = ScannerToken::generateToken();
        $scannerToken->replaceToken($token);

        return redirect()->route('admin.scanner-tokens.index')
            ->with('success', "Token regenerated for {$scannerToken->name}. The previous token no longer works.")
            ->with('generated_scanner_token', $token);
    }
}
