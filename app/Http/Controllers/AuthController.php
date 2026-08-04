<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class AuthController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function redirectToGoogle()
    {
        if (! $this->googleSsoIsConfigured()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in is not configured.',
            ]);
        }

        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        if (! $this->googleSsoIsConfigured()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in is not configured.',
            ]);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            Log::warning('Google SSO callback failed.', [
                'exception' => $exception::class,
            ]);

            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in could not be completed. Please try again.',
            ]);
        }

        $email = Str::lower(trim((string) $googleUser->getEmail()));

        if ($email === '') {
            return redirect()->route('login')->withErrors([
                'email' => 'Google did not provide an email address for this account.',
            ]);
        }

        $user = User::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->first();

        if (! $user || ! $user->is_active) {
            return redirect()->route('login')->withErrors([
                'email' => sprintf(
                    'Google signed in as %s, but that exact email is not an active system account.',
                    $email
                ),
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('auth.google_avatar', $googleUser->getAvatar());

        return redirect()->intended($this->defaultDestination($user));
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
            'transactions.view' => route('admin.entry-monitor'),
            'reports.view' => route('admin.reports.index'),
            'advertisements.view' => route('admin.advertisements.index'),
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

    private function googleSsoIsConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }
}
