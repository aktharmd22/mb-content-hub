<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SampleUsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['username' => 'sales1',  'name' => 'Aisha Tan',    'email' => 'aisha@malayznbeat.com',  'role' => 'sales',       'phone' => '+60 12-345 6789'],
            ['username' => 'sales2',  'name' => 'Daniel Lim',   'email' => 'daniel@malayznbeat.com', 'role' => 'sales',       'phone' => '+60 13-456 7890'],
            ['username' => 'writer1', 'name' => 'Priya Kumar',  'email' => 'priya@malayznbeat.com',  'role' => 'tech_team', 'phone' => null],
            ['username' => 'writer2', 'name' => 'Marcus Wong',  'email' => 'marcus@malayznbeat.com', 'role' => 'tech_team', 'phone' => null],
            ['username' => 'lead1',   'name' => 'Sarah Hassan', 'email' => 'sarah@malayznbeat.com',  'role' => 'tech_team', 'phone' => '+60 17-890 1234'],
        ];

        foreach ($users as $u) {
            User::firstOrCreate(
                ['username' => $u['username']],
                array_merge($u, [
                    'password'  => Hash::make('password'),
                    'is_active' => true,
                ])
            );
        }
    }
}
