<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScannerRegistrationController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $scanner = $request->attributes->get('scannerToken');

        return response()->json([
            'registered' => true,
            'active' => true,
            'scanner' => [
                'name' => $scanner->name,
                'deviceId' => $scanner->device_id,
            ],
        ]);
    }
}
