<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApplicationTimezoneTest extends TestCase
{
    public function test_application_timestamps_are_forced_to_philippine_time(): void
    {
        $this->assertSame('Asia/Manila', config('app.timezone'));
        $this->assertSame('Asia/Manila', date_default_timezone_get());
        $this->assertSame('+08:00', now()->format('P'));
        $this->assertSame('Asia/Manila', config('database.connections.pgsql.timezone'));

        $liveUpdates = file_get_contents(resource_path('js/admin-live-updates.js'));

        $this->assertIsString($liveUpdates);
        $this->assertStringContainsString("timeZone: 'Asia/Manila'", $liveUpdates);
    }
}
