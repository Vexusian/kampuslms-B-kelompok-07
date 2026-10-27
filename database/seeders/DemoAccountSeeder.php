<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@kampuslms.test'],
            [
                'name' => 'Admin Demo',
                'password' => Hash::make(
                    env('DEMO_PASSWORD', 'password')
                ),
                'role' => 'admin',
                'nim_nip' => null,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'dosen@kampuslms.test'],
            [
                'name' => 'Dosen Demo',
                'password' => Hash::make(
                    env('DEMO_PASSWORD', 'password')
                ),
                'role' => 'dosen',
                'nim_nip' => '1990000001',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'mahasiswa@kampuslms.test'],
            [
                'name' => 'Mahasiswa Demo',
                'password' => Hash::make(
                    env('DEMO_PASSWORD', 'password')
                ),
                'role' => 'mahasiswa',
                'nim_nip' => '1025000001',
                'email_verified_at' => now(),
            ]
        );
    }
}