<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // admin
        $admin = User::factory()->create([
            'name'              => 'ADMIN',
            'email'             => 'admin@kenali.com',
            'email_verified_at' => now(),
            'password'          => Hash::make('kenali123'),
        ]);

        $admin->assignRole('admin');

        $this->command->info('>_ Here is your admin details to login:');
        $this->command->warn($admin->email);
        $this->command->warn('Password is "kenali123"');

        // psikolog
        $psikolog = User::factory()->create([
            'name'              => 'PSIKOLOG',
            'email'             => 'psikolog@kenali.com',
            'email_verified_at' => now(),
            'password'          => Hash::make('kenali123'),
            'phone'             => '081234567890',
        ]);

        $psikolog->assignRole('psikolog');

        $psikolog->psychologistProfile()->create([
            'license_number' => 'STR-0001-2026',
            'specialization' => 'Psikolog Pendidikan',
            'is_verified'    => true,
        ]);

        $this->command->info('>_ Here is your psikolog details to login:');
        $this->command->warn($psikolog->email);
        $this->command->warn('Password is "kenali123"');

        // user
        $user = User::factory()->create([
            'name'              => 'USER',
            'email'             => 'user@kenali.com',
            'email_verified_at' => now(),
            'password'          => Hash::make('kenali123'),
            'phone'             => '089672650972',
            'life_phase'        => 'mahasiswa',
        ]);

        $user->assignRole('user');

        $this->command->info('>_ Here is your user details to login:');
        $this->command->warn($user->email);
        $this->command->warn('Password is "kenali123"');

        // bersihkan cache
        $this->command->call('cache:clear');
    }
}