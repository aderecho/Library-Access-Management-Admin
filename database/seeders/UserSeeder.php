<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
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
                'name' => 'UP Cebu Super Admin',
                'email' => 'admin@upcebu.edu.ph',
                'role' => 'super-admin',
                'password' => 'ChangeMe123!',
                'is_active' => true,
            ],
            [
                'name' => 'UP Cebu Administrator',
                'email' => 'administrator@upcebu.edu.ph',
                'role' => 'admin',
                'password' => 'ChangeMe123!',
                'is_active' => true,
            ],
            [
                'name' => 'UP Cebu Report Viewer',
                'email' => 'reports@upcebu.edu.ph',
                'role' => 'report-viewer',
                'password' => 'ChangeMe123!',
                'is_active' => true,
            ],
            [
                'name' => 'Inactive Administrator',
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
                    'name' => $user['name'],
                    'role_id' => $roles[$user['role']]->id,
                    'password' => $user['password'],
                    'is_active' => $user['is_active'],
                ]
            );
        }
    }
}
