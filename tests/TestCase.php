<?php

namespace Tests;

use App\Models\Branch;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function defaultBranch(): Branch
    {
        return Branch::firstOrCreate(['code' => 'TEST'], ['name' => 'Test Branch', 'is_active' => true]);
    }
}
