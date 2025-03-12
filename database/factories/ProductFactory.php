<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'barcode' => $this->faker->numberBetween(50000, 9000000),
            'category_id' => 1,
            'description' => $this->faker->text(100),
            'brend_id' => 1,
            'view' => $this->faker->numberBetween(50000, 9000000),
        ];
    }
}
