<?php

namespace Database\Seeders;

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

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [

            'manage-users',
            'manage-psychologists',

            'manage-criteria',
            'manage-questions',
            'manage-alternatives',
            'view-reports',

            'take-test',
            'view-own-results',
            'request-consultation',

            'view-assigned-consultations',
            'manage-consultation-notes',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Permission::whereIn('name', ['use-ai-chat'])->delete();

        $this->command->info('Default permissions added.');

        $roles = ['admin', 'user', 'psikolog'];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }
        $this->command->info('Default roles added.');

        $adminRole = Role::findByName('admin');
        $adminRole->syncPermissions(Permission::all());

        $userRole = Role::findByName('user');
        $userRole->syncPermissions([
            'take-test',
            'view-own-results',
            'request-consultation',
        ]);

        $psikologRole = Role::findByName('psikolog');
        $psikologRole->syncPermissions([
            'view-assigned-consultations',
            'manage-consultation-notes',
        ]);

        $this->command->info('Permissions assigned to roles.');
    }
}
