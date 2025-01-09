<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->count(10)->create([
            'usertype' => 'common',
        ]);

        // Cria 5 lojistas
        User::factory()->count(5)->create([
            'usertype' => 'merchant',
        ]);
    }
}
