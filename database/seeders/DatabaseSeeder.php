<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            UserSeeder::class,
            SparePartSeeder::class,
            SawKriteriaSeeder::class,
            SawHistorisSeeder::class,
            NeedlistSeeder::class,
        ]);
    }
}