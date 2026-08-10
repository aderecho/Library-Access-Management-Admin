<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AdminDestination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class AuthController extends Controller
{
    public function __construct(private readonly AdminDestination $adminDestination) {}

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

        $intendedUrl = $request->session()->pull('url.intended');
        $destination = $this->scannerSettingsDestination($user, $intendedUrl)
            ?? $this->adminDestination->urlFor($user)
            ?? route('login');

        return redirect()->to($destination);
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function keepAlive(Request $request)
    {
        $request->session()->put('session_last_extended_at', now()->timestamp);

        return response()->json([
            'message' => 'Session extended.',
            'expires_at' => now()->addMinutes((int) config('session.lifetime'))->timestamp,
        ]);
    }

    private function scannerSettingsDestination(User $user, mixed $intendedUrl): ?string
    {
        if (! is_string($intendedUrl) || ! $user->hasPermission('scanner-tokens.update')) {
            return null;
        }

        $path = parse_url($intendedUrl, PHP_URL_PATH);

        if (! is_string($path) || ! preg_match('#^/scanner/settings/authorize/([A-Za-z0-9]+)$#', $path, $matches)) {
            return null;
        }

        return route('scanner.settings.authorize', $matches[1]);
    }

    private function googleSsoIsConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }
}
