<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->get(['id', 'permissions'])->each(function ($role): void {
            $permissions = json_decode($role->permissions ?: '[]', true) ?: [];

            if (! in_array('transactions.view', $permissions, true)) {
                return;
            }

            $permissions[] = 'entry-monitor.view';

            DB::table('roles')->where('id', $role->id)->update([
                'permissions' => json_encode(array_values(array_unique($permissions))),
                'updated_at' => now(),
            ]);
        });

        $entryMonitor = DB::table('roles')->where('slug', 'entry-monitor')->first();

        if ($entryMonitor) {
            $permissions = json_decode($entryMonitor->permissions ?: '[]', true) ?: [];
            $permissions[] = 'entry-monitor.view';

            DB::table('roles')->where('id', $entryMonitor->id)->update([
                'name' => 'Entry Monitor',
                'description' => 'Branch-assigned staff who monitor live library entries.',
                'permissions' => json_encode(array_values(array_unique($permissions))),
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('roles')->insert([
            'name' => 'Entry Monitor',
            'slug' => 'entry-monitor',
            'description' => 'Branch-assigned staff who monitor live library entries.',
            'permissions' => json_encode(['entry-monitor.view']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Preserve role assignments and permissions during rollbacks.
    }
};
