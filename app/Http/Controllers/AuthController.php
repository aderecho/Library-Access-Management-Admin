<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'The provided credentials are invalid or the account is inactive.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended($this->defaultDestination($request->user()));
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function defaultDestination($user): string
    {
        $destinations = [
            'dashboard.view' => route('admin.dashboard'),
            'transactions.view' => route('admin.transactions.index'),
            'reports.view' => route('admin.reports.index'),
            'users.view' => route('admin.users.index'),
            'roles.view' => route('admin.roles.index'),
        ];

        foreach ($destinations as $permission => $route) {
            if ($user->hasPermission($permission)) {
                return $route;
            }
        }

        return route('login');
    }
}
