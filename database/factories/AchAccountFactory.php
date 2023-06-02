<?php
namespace Unbank\Kyckglobal\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Unbank\Kyckglobal\AchAccount;

class  AchAccountFactory extends Factory {
    protected $model = AchAccount::class;

    public function definition()
    {
       return [
           'payee_id' => $this->faker->uuid(),
           'routing_number' => $this->faker->numberBetween(100000000,999999999),
           'account_number' => $this->faker->numberBetween(100000000,999999999),
           'account_name' => $this->faker->randomElement(['Checking', 'Savings']) . " Account",
           'account_type' => $this->faker->randomElement(['Checking', 'Savings']),
           'data' => []
       ];
    }
}