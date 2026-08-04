<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->insertOrIgnore([
            'name' => 'ITC-Tech',
            'slug' => 'itc-tech',
            'description' => 'ITC technical support role. Assign permissions as required.',
            'permissions' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Keep the role and any user assignments intact during rollbacks.
    }
};
