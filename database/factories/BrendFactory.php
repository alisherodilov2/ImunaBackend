<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BrendFactory extends Factory
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
            'status' => 1,
            'status' => 1,
        ];
    }
}
