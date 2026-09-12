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
        $admin = User::firstOrCreate(
            ['email' => 'admin@kenali.com'],
            [
                'name'              => 'ADMIN',
                'email_verified_at' => now(),
                'password'          => Hash::make('kenali123'),
            ]
        );

        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        $this->command->info('>_ Here is your admin details to login:');
        $this->command->warn($admin->email);
        $this->command->warn('Password is "kenali123"');

        // psikolog
        $psikolog = User::firstOrCreate(
            ['email' => 'psikolog@kenali.com'],
            [
                'name'              => 'PSIKOLOG',
                'email_verified_at' => now(),
                'password'          => Hash::make('kenali123'),
                'phone'             => '081234567890',
            ]
        );

        if (! $psikolog->hasRole('psikolog')) {
            $psikolog->assignRole('psikolog');
        }

        if (! $psikolog->psychologistProfile) {
            $psikolog->psychologistProfile()->create([
                'license_number' => 'STR-0001-2026',
                'specialization' => 'Psikolog Pendidikan',
                'is_verified'    => true,
            ]);
        }

        $this->command->info('>_ Here is your psikolog details to login:');
        $this->command->warn($psikolog->email);
        $this->command->warn('Password is "kenali123"');

        // user
        $user = User::firstOrCreate(
            ['email' => 'user@kenali.com'],
            [
                'name'              => 'USER',
                'email_verified_at' => now(),
                'password'          => Hash::make('kenali123'),
                'phone'             => '089672650972',
                'life_phase'        => 'mahasiswa',
            ]
        );

        if (! $user->hasRole('user')) {
            $user->assignRole('user');
        }

        $this->command->info('>_ Here is your user details to login:');
        $this->command->warn($user->email);
        $this->command->warn('Password is "kenali123"');

        // bersihkan cache
        $this->command->call('cache:clear');
    }
}