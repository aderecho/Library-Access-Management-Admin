<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RoleUserMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_backfills_legacy_roles_and_rollback_preserves_assignments(): void
    {
        $role = Role::create([
            'name' => 'Legacy Role',
            'slug' => 'legacy-role',
            'permissions' => [],
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $migration = require database_path('migrations/2026_08_10_000023_create_role_user_table.php');

        $migration->up();

        $this->assertDatabaseHas('role_user', [
            'user_id' => $user->id,
            'role_id' => $role->id,
        ]);

        $migration->down();

        $this->assertTrue(Schema::hasTable('role_user'));
        $this->assertDatabaseHas('role_user', [
            'user_id' => $user->id,
            'role_id' => $role->id,
        ]);
    }
}
