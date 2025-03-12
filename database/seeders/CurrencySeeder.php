<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $currency = [
            [
                'id' => 1,
                'user_id' => '1',
                'price' => '12300',
                // 'date' => '01-06-2000',

            ],

        ];
        Currency::insert($currency);
    }
}
