<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ScannerSettingsSsoController extends Controller
{
    private const CACHE_PREFIX = 'scanner-settings-sso:';

    private const EXPIRES_IN_SECONDS = 300;

    public function start(): JsonResponse
    {
        $requestId = Str::random(64);

        Cache::put($this->cacheKey($requestId), [
            'status' => 'pending',
        ], now()->addSeconds(self::EXPIRES_IN_SECONDS));

        return response()->json([
            'requestId' => $requestId,
            'authorizationUrl' => route('scanner.settings.authorize', $requestId),
            'expiresInSeconds' => self::EXPIRES_IN_SECONDS,
        ]);
    }

    public function status(string $requestId): JsonResponse
    {
        $request = Cache::get($this->cacheKey($requestId));

        if (! $request) {
            return response()->json([
                'message' => 'The Google sign-in request expired. Please try again.',
            ], 410);
        }

        if (($request['status'] ?? null) !== 'approved') {
            return response()->json([
                'authenticated' => false,
                'status' => 'pending',
            ], 202);
        }

        Cache::forget($this->cacheKey($requestId));

        return response()->json([
            'authenticated' => true,
            'expiresInSeconds' => self::EXPIRES_IN_SECONDS,
        ]);
    }

    public function authorizeRequest(Request $request, string $requestId): View
    {
        $cacheKey = $this->cacheKey($requestId);

        if (! Cache::has($cacheKey)) {
            return view('auth.scanner-settings-sso-result', [
                'authorized' => false,
                'title' => 'Request expired',
                'message' => 'Return to the scanner and start Google sign-in again.',
            ]);
        }

        if (! $request->user()->hasPermission('scanner-tokens.update')) {
            return view('auth.scanner-settings-sso-result', [
                'authorized' => false,
                'title' => 'Access denied',
                'message' => 'This account is not allowed to change scanner settings.',
            ]);
        }

        Cache::put($cacheKey, [
            'status' => 'approved',
            'userId' => $request->user()->getKey(),
        ], now()->addSeconds(self::EXPIRES_IN_SECONDS));

        return view('auth.scanner-settings-sso-result', [
            'authorized' => true,
            'title' => 'Scanner settings authorized',
            'message' => 'Return to the scanner application. You may close this window.',
        ]);
    }

    private function cacheKey(string $requestId): string
    {
        return self::CACHE_PREFIX.$requestId;
    }
}
