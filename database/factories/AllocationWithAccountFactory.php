<?php
namespace Unbank\Kyckglobal\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Unbank\Kyckglobal\AchAccount;
use Unbank\Kyckglobal\AllocationWithAccount;

class  AllocationWithAccountFactory extends Factory {

    protected $model = AllocationWithAccount::class;

    public function definition()
    {
       return [
           'user_id' => $this->faker->randomNumber(),
           'payee_id' => $this->faker->randomNumber(),
           'account_id' => $this->faker->numberBetween(1, 100),
           'account_type' => 'Ach',
           'allocation' => 0,
           'disbursable_id' => $this->faker->randomNumber(),
           'disbursable_type' => 'Unbank\Kyckglobal\AchAccount'
       ];
    }
}
