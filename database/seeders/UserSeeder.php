<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultBranch = Branch::firstOrCreate(['code' => 'MAIN'], ['name' => 'Main Library', 'is_active' => true]);
        $roles = collect([
            [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'description' => 'Full access, including user accounts and roles.',
                'permissions' => collect(config('permissions.groups'))->flatMap(fn (array $permissions) => array_keys($permissions))->values()->all(),
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Access to dashboard, transactions, and reports.',
                'permissions' => [
                    'dashboard.view',
                    'transactions.view',
                    'reports.view',
                    'reports.export',
                    'advertisements.view',
                    'advertisements.create',
                ],
            ],
            [
                'name' => 'ITC-Tech',
                'slug' => 'itc-tech',
                'description' => 'ITC technical support role with branch configuration access.',
                'permissions' => [
                    'branches.view',
                    'branches.create',
                    'branches.update',
                ],
            ],
            [
                'name' => 'Report Viewer',
                'slug' => 'report-viewer',
                'description' => 'Reserved for read-only report access.',
                'permissions' => [
                    'dashboard.view',
                    'reports.view',
                    'reports.export',
                ],
            ],
        ])->mapWithKeys(function (array $role) {
            $model = Role::updateOrCreate(
                ['slug' => $role['slug']],
                $role
            );

            return [$role['slug'] => $model];
        });

        $users = [
            [
                'first_name' => 'UP Cebu',
                'middle_name' => null,
                'last_name' => 'Super Admin',
                'suffix' => null,
                'email' => 'admin@upcebu.edu.ph',
                'role' => 'super-admin',
                'password' => 'ChangeMe123!',
                'is_active' => true,
            ],
            [
                'first_name' => 'UP Cebu',
                'middle_name' => null,
                'last_name' => 'Administrator',
                'suffix' => null,
                'email' => 'administrator@upcebu.edu.ph',
                'role' => 'admin',
                'password' => 'ChangeMe123!',
                'is_active' => true,
            ],
            [
                'first_name' => 'UP Cebu',
                'middle_name' => null,
                'last_name' => 'Report Viewer',
                'suffix' => null,
                'email' => 'reports@upcebu.edu.ph',
                'role' => 'report-viewer',
                'password' => 'ChangeMe123!',
                'is_active' => true,
            ],
            [
                'first_name' => 'Inactive',
                'middle_name' => null,
                'last_name' => 'Administrator',
                'suffix' => null,
                'email' => 'inactive.admin@upcebu.edu.ph',
                'role' => 'admin',
                'password' => 'ChangeMe123!',
                'is_active' => false,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'first_name' => $user['first_name'],
                    'middle_name' => $user['middle_name'],
                    'last_name' => $user['last_name'],
                    'suffix' => $user['suffix'],
                    'branch_id' => $defaultBranch->id,
                    'role_id' => $roles[$user['role']]->id,
                    'password' => $user['password'],
                    'is_active' => $user['is_active'],
                ]
            );
        }
    }
}
