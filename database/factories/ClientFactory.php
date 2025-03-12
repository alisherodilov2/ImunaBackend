<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {

        // 'first_name',
        // 'last_name',
        // 'parent_name',
        // 'data_birth',
        // 'phone',
        // 'citizenship',
        // 'sex',
        // 'price',
        // 'person_id',
        // 'probirka_count',
        // 'doctor_id', ///tekshirigan shifokor
        // 'parent_id',
        // 'user_id',
        // 'department_id',
        // 'total_price',
        // 'service_count',
        // 'debt_price',
        // 'discount',
        // // 'discount_price',
        // 'is_pay',
        // 'payment_deadline',
        // 'pay_total_price',
        // // 'queue_number',
        // 'duration',
        // 'use_duration',
        // 'start_time',
        // 'is_check_doctor',
        // 'room_id',
        // 'back_total_price',
        // 'address',
        // 'use_status',
        // 'department_count',
        // 'finish_department_count',
        // 'referring_doctor_id',
        // 'advertisements_id',
        // 'balance'

        $sex = $this->faker->randomElement(['male', 'female']);

        // Jinsga qarab ism tanlash
        $firstName = $sex === 'male' 
            ? $this->faker->firstNameMale 
            : $this->faker->firstNameFemale;

        return [
            'first_name' => $firstName,
            'last_name' => $this->faker->lastName,
            'sex' => $sex,
            'address' => $this->faker->address, // Tasodifiy manzil
            'phone' => $this->faker->phoneNumber, // Tasodifiy manzil
            'data_birth'=> $this->faker->dateTimeBetween('-44 years', '-19 years')->format('Y-m-d'),
            'user_id' => User::where('role', User::USER_ROLE_RECEPTION)->inRandomOrder()->first()->id,
        ];
    }
}
