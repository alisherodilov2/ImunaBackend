<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    protected $rule = [User::USER_ROLE_SELLER,User::USER_ROLE_CUSTOMER,User::USER_ROLE_MANAGER];
    
    public function definition()
    {
        return [
                'email' => $this->faker->unique()->safeEmail(),
                // 'role' => $this->rule[$this->faker->numberBetween(0, 2)],
                'role' => $this->rule[1],
                'status' => 0,
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }
}
