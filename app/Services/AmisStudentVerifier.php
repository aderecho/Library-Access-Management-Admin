<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmisStudentVerifier
{
    public function verify(string $campusId): array
    {
        $baseUrl = rtrim((string) config('services.amis.base_url'), '/');

        try {
            $response = Http::acceptJson()
                ->connectTimeout((int) config('services.amis.connect_timeout_seconds', 2))
                ->timeout((int) config('services.amis.timeout_seconds', 4))
                ->get($baseUrl.'/api/student-info/'.rawurlencode($campusId));
        } catch (ConnectionException $exception) {
            Log::warning('AMIS student verification connection failed.', [
                'campus_id' => $campusId,
                'error' => $exception->getMessage(),
            ]);

            return ['status' => 'unavailable', 'student' => null];
        }

        if ($response->status() === 404) {
            return ['status' => 'not_found', 'student' => null];
        }

        if (! $response->successful()) {
            Log::warning('AMIS student verification returned an unsuccessful response.', [
                'campus_id' => $campusId,
                'http_status' => $response->status(),
            ]);

            return ['status' => 'unavailable', 'student' => null];
        }

        $records = $response->json('student');

        if (! is_array($records) || $records === []) {
            return ['status' => 'not_found', 'student' => null];
        }

        $records = array_is_list($records) ? $records : [$records];
        $student = collect($records)->first(
            fn ($record) => is_array($record) && (string) ($record['campus_id'] ?? '') === $campusId
        );

        if (! is_array($student)) {
            return ['status' => 'not_found', 'student' => null];
        }

        return ['status' => 'verified', 'student' => $student];
    }
}
