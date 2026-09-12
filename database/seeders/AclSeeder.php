<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AclSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions.
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Daftar permission default aplikasi Kenali.
        $permissions = [
            // Manajemen user & psikolog (khusus admin)
            'manage-users',
            'manage-psychologists',

            // Manajemen konten SPK (khusus admin)
            'manage-criteria',
            'manage-questions',
            'manage-alternatives',
            'view-reports',

            // Fitur untuk user umum
            'take-test',
            'view-own-results',
            'request-consultation',
            'use-ai-chat',

            // Fitur untuk psikolog
            'view-assigned-consultations',
            'manage-consultation-notes',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        $this->command->info('Default permissions added.');

        // Daftar role default aplikasi Kenali.
        $roles = ['admin', 'user', 'psikolog'];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }
        $this->command->info('Default roles added.');

        // Assign permission ke tiap role.
        $adminRole = Role::findByName('admin');
        $adminRole->syncPermissions(Permission::all());

        $userRole = Role::findByName('user');
        $userRole->syncPermissions([
            'take-test',
            'view-own-results',
            'request-consultation',
            'use-ai-chat',
        ]);

        $psikologRole = Role::findByName('psikolog');
        $psikologRole->syncPermissions([
            'view-assigned-consultations',
            'manage-consultation-notes',
        ]);

        $this->command->info('Permissions assigned to roles.');
    }
}