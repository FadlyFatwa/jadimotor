<?php

namespace Database\Seeders;

use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        User::create([
            'name' => 'Procurement',
            'username' => 'procurement',
            'email' => 'procurement@example.com', // Opsional, jika masih diperlukan
            'password' => Hash::make('password123'), // Ganti dengan password yang aman
            'role' => 'procurement',
        ]);
    
        User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@example.com', // Opsional
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);
    
        User::create([
            'name' => 'Kasir',
            'username' => 'kasir',
            'email' => 'kasir@example.com', // Opsional
            'password' => Hash::make('password123'),
            'role' => 'kasir',
        ]);
        User::create([
            'name' => 'Supervisor',
            'email' => 'supervisor@example.com',
            'username' => 'supervisor',
            'password' => Hash::make('password123'), // Ganti dengan password yang diinginkan
            'role' => 'supervisor',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Tambahkan user Gudang
        User::create([
            'name' => 'Gudang',
            'email' => 'gudang@example.com',
            'username' => 'gudang',
            'password' => Hash::make('password123'),
            'role' => 'gudang',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
