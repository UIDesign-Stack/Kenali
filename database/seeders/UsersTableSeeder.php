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

        if (app()->environment('production')) {
            $this->command->error('UsersTableSeeder tidak dijalankan di production. Buat akun admin secara manual dengan password acak.');

            return;
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@kenali.com'],
            [
                'name'              => 'ADMIN',
                'email_verified_at' => now(),
                'password'          => Hash::make('kenali123'),
            ]
        );

        $this->markVerified($admin);

        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        $this->command->info('>_ Here is your admin details to login:');
        $this->command->warn($admin->email);
        $this->command->warn('Password is "kenali123"');

        $psikolog = User::firstOrCreate(
            ['email' => 'psikolog@kenali.com'],
            [
                'name'              => 'PSIKOLOG',
                'email_verified_at' => now(),
                'password'          => Hash::make('kenali123'),
                'phone'             => '081234567890',
            ]
        );

        $this->markVerified($psikolog);

        if (! $psikolog->hasRole('psikolog')) {
            $psikolog->assignRole('psikolog');
        }

        if (! $psikolog->psychologistProfile) {
            $profile = $psikolog->psychologistProfile()->make([
                'license_number' => 'STR-0001-2026',
                'specialization' => 'Psikolog Pendidikan',
            ]);

            $profile->forceFill([
                'is_verified'  => true,
                'is_available' => true,
            ])->save();
        }

        $this->command->info('>_ Here is your psikolog details to login:');
        $this->command->warn($psikolog->email);
        $this->command->warn('Password is "kenali123"');

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

        $this->markVerified($user);

        if (! $user->hasRole('user')) {
            $user->assignRole('user');
        }

        $this->command->info('>_ Here is your user details to login:');
        $this->command->warn($user->email);
        $this->command->warn('Password is "kenali123"');

        // bersihkan cache
        $this->command->call('cache:clear');
    }

    /**
     * email_verified_at tidak ada di $fillable User, jadi nilai yang dikirim lewat
     * firstOrCreate([...]) dibuang diam-diam. Diisi di sini lewat forceFill.
     */
    private function markVerified(User $user): void
    {
        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }
    }
}
