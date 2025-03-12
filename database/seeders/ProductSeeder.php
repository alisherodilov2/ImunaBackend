<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $product = [
            [
                'id' => 1,
                'name' => 'арматура 10',
                'category_id' => 1,
            ],
            [
                'id' => 2,
                'name' => "g'isht",
                'category_id' => 1,
            ],
            [
                'id' => 3,
                'name' => 'sement',
                'category_id' => 1,
            ],
            [
                'id' => 4,
                'name' => 'Qum',
                'category_id' => 1,
            ],
        ];
        Product::insert($product);

    }
}
