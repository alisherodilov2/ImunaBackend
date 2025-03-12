<?php

namespace Database\Seeders;

use App\Models\Customer ;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $customer = [
            [
                'id' => 1,
                'name' => 'Sobitxon armaturachi optovik',
                'user_id' => 1,
                'phone' => 1,
                'comment' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Karimjon optovik sement',
                'user_id' => 1,
                'phone' => 1,
                'comment' => 1,
            ],
        ];
        Customer::insert($customer);
    }
}
