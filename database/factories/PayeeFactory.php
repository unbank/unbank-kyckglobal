<?php

namespace Unbank\Kyckglobal\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Unbank\Kyckglobal\AchAccount;
use Unbank\Kyckglobal\Payee;

class  PayeeFactory extends Factory
{
    protected $model = Payee::class;

    public function definition()
    {
        return [
            'is_active' => $this->faker->boolean(),
            'user_id' => $this->faker->uuid(),
            'payee_id' => $this->faker->uuid(),
            'email' => $this->faker->email(),
            'phone_number' => $this->faker->phoneNumber(),
            'service_provider' => $this->faker->city(),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'verified' => $this->faker->numberBetween(0,2),
            'data' => []
        ];
    }
}