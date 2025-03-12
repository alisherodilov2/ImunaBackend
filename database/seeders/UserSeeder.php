<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::updateOrCreate([
            'id' => 1,
        ], [
            'role' => User::USER_ROLE_SUPPER_ADMIN,
            'password' => Hash::make('1111'),
            'name' => 'admin',
            'login' => 'admin',

        ]);
        User::updateOrCreate([
            'id' => 2,
        ], [
            'role' => User::USER_ROLE_DIRECTOR,
            'password' => Hash::make('1'),
            'full_name' => 'Murod',
            'name' => 'direktor',
            'login' => 'reg',
            'owner_id' => 1,

        ]);
        User::updateOrCreate([
            'id' => 3,
        ], [
            'role' => User::USER_ROLE_RECEPTION,
            'password' => Hash::make('2'),
            'name' => 'reg',
            'full_name' => 'Odil',
            'login' => 'reg',
            'owner_id' => 2,

        ]);
        User::updateOrCreate([
            'id' => 4,
        ], [
            'role' => User::USER_ROLE_DOCTOR,
            'password' => Hash::make('3'),
            'name' => 'doc',
            'full_name' => 'Urolog Kozim',
            'login' => 'doc',
            'owner_id' => 2,
        ]);
        User::updateOrCreate([
            'id' => 5,
        ], [
            'role' => User::USER_ROLE_DOCTOR,
            'password' => Hash::make('4'),
            'name' => 'doc',
            'full_name' => 'Labarand Jamshid',
            'login' => 'doc',
            'owner_id' => 2,
        ]);
    }
}
