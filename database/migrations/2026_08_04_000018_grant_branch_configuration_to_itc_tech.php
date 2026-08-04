<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $role = DB::table('roles')->where('slug', 'itc-tech')->first();

        if (! $role) {
            return;
        }

        $permissions = array_values(array_unique(array_merge(
            json_decode($role->permissions ?: '[]', true) ?: [],
            ['branches.view', 'branches.create', 'branches.update'],
        )));

        DB::table('roles')->where('id', $role->id)->update([
            'permissions' => json_encode($permissions),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Preserve role permission assignments during rollbacks.
    }
};
