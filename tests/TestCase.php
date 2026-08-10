<?php

namespace Tests;

use App\Models\Branch;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        if (is_file(dirname(__DIR__).'/bootstrap/cache/config.php')) {
            throw new RuntimeException(
                'Refusing to run tests with cached configuration. Run php artisan config:clear so PHPUnit uses its SQLite in-memory database.'
            );
        }

        return parent::createApplication();
    }

    protected function defaultBranch(): Branch
    {
        return Branch::firstOrCreate(['code' => 'TEST'], ['name' => 'Test Branch', 'is_active' => true]);
    }
}
