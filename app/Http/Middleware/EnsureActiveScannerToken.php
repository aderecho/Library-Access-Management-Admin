<?php

namespace App\Http\Middleware;

use App\Models\ScannerToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveScannerToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = trim((string) $request->header('X-Scanner-Token'));

        if ($token === '') {
            return response()->json(['message' => 'Scanner token is required.'], 401);
        }

        $scannerToken = ScannerToken::where('token_hash', hash('sha256', $token))->first();

        if (! $scannerToken) {
            return response()->json(['message' => 'Scanner application is not registered.'], 401);
        }

        if (! $scannerToken->is_active) {
            return response()->json(['message' => 'Scanner registration is deactivated.'], 403);
        }

        if (! $scannerToken->branch_id || ! $scannerToken->branch?->is_active) {
            return response()->json(['message' => 'Scanner is not assigned to an active branch.'], 403);
        }

        $scannerToken->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('scannerToken', $scannerToken);

        return $next($request);
    }
}
