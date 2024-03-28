<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('admin_users')->insert([
            'name' => 'Sha Admin',
            'email' => 'admin@email.com',
            'password' => bcrypt('rahasia123'),
        ]);
    }
}
