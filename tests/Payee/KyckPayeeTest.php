<?php
namespace Unbank\Kyckglobal\Tests\Payee;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Unbank\Kyckglobal\Payee;
use Unbank\Kyckglobal\Tests\TestCase;
use Unbank\Kyckglobal\Traits\KyckPayeeTrait;

class KyckPayeeTest extends TestCase {

    use RefreshDatabase;

    /**
     * @group Payee
     */
    public function test_can_use_kyc_payee_trait() {
        $payee = Payee::factory()->create();
        $trait = $this->getObjectForTrait(KyckPayeeTrait::class);
        $this->assertTrue($trait->generateAllocationData());
    }

}