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
        $origin = rtrim((string) config('services.amis.origin'), '/');
        $startedAt = microtime(true);
        $logContext = [
            'base_url' => $baseUrl,
            'origin' => $origin,
            'campus_id' => $this->maskCampusId($campusId),
        ];

        Log::info('AMIS student verification request started.', $logContext);

        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'Origin' => 'https://las.upcebu.edu.ph',
                ])
                ->connectTimeout((int) config('services.amis.connect_timeout_seconds', 2))
                ->timeout((int) config('services.amis.timeout_seconds', 4))
                ->get($baseUrl.'/api/student-info/'.rawurlencode($campusId));
        } catch (ConnectionException $exception) {
            Log::warning('AMIS student verification connection failed.', $logContext + [
                'duration_ms' => $this->durationInMilliseconds($startedAt),
                'error' => str_replace($campusId, $this->maskCampusId($campusId), $exception->getMessage()),
            ]);

            return ['status' => 'unavailable', 'student' => null];
        }

        Log::info('AMIS student verification response received.', $logContext + [
            'connected' => true,
            'http_status' => $response->status(),
            'duration_ms' => $this->durationInMilliseconds($startedAt),
        ]);

        if ($response->status() === 404) {
            Log::info('AMIS student verification completed.', $logContext + [
                'verification_status' => 'not_found',
            ]);

            return ['status' => 'not_found', 'student' => null];
        }

        if (! $response->successful()) {
            Log::warning('AMIS student verification returned an unsuccessful response.', $logContext + [
                'http_status' => $response->status(),
                'verification_status' => 'unavailable',
            ]);

            return ['status' => 'unavailable', 'student' => null];
        }

        $records = $response->json('student');

        if (! is_array($records) || $records === []) {
            Log::info('AMIS student verification completed.', $logContext + [
                'verification_status' => 'not_found',
                'reason' => 'empty_student_payload',
            ]);

            return ['status' => 'not_found', 'student' => null];
        }

        $records = array_is_list($records) ? $records : [$records];
        $student = collect($records)->first(
            fn ($record) => is_array($record) && (string) ($record['campus_id'] ?? '') === $campusId
        );

        if (! is_array($student)) {
            Log::info('AMIS student verification completed.', $logContext + [
                'verification_status' => 'not_found',
                'reason' => 'campus_id_mismatch',
            ]);

            return ['status' => 'not_found', 'student' => null];
        }

        Log::info('AMIS student verification completed.', $logContext + [
            'verification_status' => 'verified',
        ]);

        return ['status' => 'verified', 'student' => $student];
    }

    private function maskCampusId(string $campusId): string
    {
        $visibleLength = min(4, strlen($campusId));

        return str_repeat('*', max(0, strlen($campusId) - $visibleLength))
            .substr($campusId, -$visibleLength);
    }

    private function durationInMilliseconds(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
