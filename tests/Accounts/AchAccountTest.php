<?php
namespace Unbank\Kyckglobal\Tests\Accounts;

use Unbank\Kyckglobal\AchAccount;
use Unbank\Kyckglobal\Tests\TestCase;

class AchAccountTest extends TestCase {

    /**
     * @group Account
     */
    public function test_can_create_an_ach_account_model() {
        $achAccount = AchAccount::factory()->create();
        $this->assertModelExists($achAccount);
    }
}