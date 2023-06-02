<?php
namespace Unbank\Kyckglobal\Tests\Payee;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Unbank\Kyckglobal\AchAccount;
use Unbank\Kyckglobal\Payee;
use Unbank\Kyckglobal\Tests\TestCase;
use Unbank\Kyckglobal\Traits\KyckPayeeTrait;

class KyckPayeeTest extends TestCase {

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kyckObject = new class {
            use KyckPayeeTrait;
        };
        $this->kyckObject->phone_number_base = "+1 876 555 5555";

        $this->kyckObject->payee = Payee::factory()->create();
        $this->kyckObject->achAccount = AchAccount::factory()->create();
    }

    /**
     * @group Payee
     */
    public function test_can_use_kyc_payee_trait() {
        $payee = $this->kyckObject->generateAllocationData();
        $this->assertIsArray($payee);
    }

    /**
     * @group Payee
     */
    public function test_can_generate_ncr_allocation_using_kyc_payee_trait() {
        $payee = $this->kyckObject->generateAllocationData();

        $this->assertIsArray($payee);
        $this->assertArrayHasKey('payeeId', $payee);
        $this->assertArrayHasKey('contactInfo', $payee);
        $this->assertArrayHasKey('mobile', $payee['contactInfo']);
        $this->assertArrayHasKey('sendSMS', $payee['contactInfo']);
        $this->assertIsArray($payee['paymentTypes']);
        $this->assertEquals('NCRpay360', $payee['paymentTypes'][0]);
    }

    /**
     * @group Payee
     */
    public function test_that_generated_ncr_allocation_is_correct() {
        $payee = $this->kyckObject->generateAllocationData();
        $this->assertEquals(100, $payee['ncrPay360']['ncrPay360Allocation']);
    }

    /**
     * @group Payees
     */
    public function test_can_generate_ach_allocation_using_kyc_payee_trait() {
        $payee = $this->kyckObject->generateAllocationData('ach');

        $this->assertIsArray($payee);

        $this->assertArrayHasKey('payeeId', $payee);
        $this->assertArrayHasKey('payeeFinancialAccounts', $payee);
        $this->assertArrayHasKey('routingNumber', $payee['payeeFinancialAccounts']);
        $this->assertArrayHasKey('accountNumber', $payee['payeeFinancialAccounts']);
        $this->assertArrayHasKey('accountType', $payee['payeeFinancialAccounts']);
        $this->assertArrayHasKey('allocation', $payee['payeeFinancialAccounts']);

        $this->assertIsArray($payee['paymentTypes']);
        $this->assertEquals('NCRpay360', $payee['paymentTypes'][0]);
        $this->assertEquals('ach', $payee['paymentTypes'][1]);

        $this->assertEquals(100, $payee['payeeFinancialAccounts']['allocation']);

    }
    /**
     * @group Payee
     */
    public function test_will_return_null_if_no_payee() {
        $trait = $this->getObjectForTrait(KyckPayeeTrait::class);
        $this->assertEquals(null, $trait->getOrCreatePayee());
    }


}