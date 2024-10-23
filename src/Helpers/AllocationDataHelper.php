<?php

namespace Unbank\Kyckglobal\Helpers;

use Unbank\Kyckglobal\AllocationWithAccount;
use Unbank\Kyckglobal\Payee;

class AllocationDataHelper {

    protected array $allocation = [
        "payeeNcrPay360Account" => [],
        "payeePaypalAccount" => [],
        // "payeeVenmoAccount" => [],
        // "pushToCardAccount" => []
    ];
    protected ?array $payee_data;


    public function __construct(protected Payee $payee)
    {
        $this->payee_data = $payee->data;
    }

    public function payee(): Payee {
        return $this->payee;
    }

    public function ncrpay360Accounts() {
        if ( empty($this->payee_data["payeeNcrPay360Account"]) ) {
            return null;
        }
        return collect($this->payee_data["payeeNcrPay360Account"]);
    }

    public function ncrpay360DisbursementId(?string $phone_number) {
        $accounts = $this->ncrpay360Accounts();
        if ( empty($accounts) ) {
            return null;
        }

        $account = $accounts->where('accountNumber', $phone_number)->first();
        if ( empty($account) ) {
            $account = $accounts[0];
        }
        return $account["payeeDisbursementAccountId"];
    }

    public function venmoAccounts() {
        if ( empty($this->payee_data["payeeVenmoAccount"]) ) {
            return null;
        }
        return collect($this->payee_data["payeeVenmoAccount"]);
    }

    public function venmoDisbursementId(?string $phone_number) {
        $accounts = $this->venmoAccounts();
        if ( empty($accounts) ) {
            return null;
        }

        $account = $accounts->where('paypalId', $phone_number)->first();
        if ( empty($account) ) {
            $account = $accounts[0];
        }
        return $account["payeeDisbursementAccountId"];
    }

    public function paypalAccounts() {
        if ( empty($this->payee_data["payeePaypalAccount"]) ) {
            return null;
        }
        return collect($this->payee_data["payeePaypalAccount"]);
    }

    public function paypalDisbursementId(?string $email) {
        $accounts = $this->paypalAccounts();
        if ( empty($accounts) ) {
            return null;
        }

        $account = $accounts->where('paypalId', $email)->first();
        if ( empty($account) ) {
            $account = $accounts[0];
        }
        return $account["payeeDisbursementAccountId"];
    }


    public function pushToCardAccounts() {
        if ( empty($this->payee_data["pushToCardAccount"]) ) {
            return null;
        }
        return collect($this->payee_data["pushToCardAccount"]);
    }

    public function pushToCardDisbursementId(?string $tokenReferenceID) {
        $accounts = $this->pushToCardAccounts();
        if ( empty($accounts) ) {
            return null;
        }

        $account = $accounts->where('tokenReferenceID', $tokenReferenceID)->first();
        if ( empty($account) ) {
            $account = $accounts->last();
        }
        return $account["payeeDisbursementAccountId"];
    }


    public function getAllocationByAccounts(): array {

        $accounts = [];

        try {

            $ncrpayAccounts = $this->ncrpay360Accounts();
            if ( !empty($ncrpayAccounts) ) {
                foreach ($ncrpayAccounts as $account) {
                    $accounts[] = [
                        "account_type" => AllocationWithAccount::ACCOUNT_TYPE_NCRPAY360,
                        'account_number' => $account['accountNumber'],
                        "disbursement_id" => $account["payeeDisbursementAccountId"],
                        "allocation" => $account['allocation']
                    ];
                }
            }

            $venmoAccounts = $this->venmoAccounts();
            if ( !empty($venmoAccounts) ) {
                foreach ($venmoAccounts as $account) {
                    $accounts[] = [
                        "account_type" => AllocationWithAccount::ACCOUNT_TYPE_VENMO,
                        'account_number' => $account['paypalId'],
                        "disbursement_id" => $account["payeeDisbursementAccountId"],
                        "allocation" => $account['allocation']
                    ];
                }
            }


            $paypalAccounts = $this->paypalAccounts();
            if ( !empty($paypalAccounts) ) {
                foreach ($paypalAccounts as $account) {
                    $accounts[] = [
                        "account_type" => AllocationWithAccount::ACCOUNT_TYPE_PAYPAL,
                        'account_number' => $account['paypalId'],
                        "disbursement_id" => $account["payeeDisbursementAccountId"],
                        "allocation" => $account['allocation']
                    ];
                }
            }


            $pushToCardAccounts = $this->pushToCardAccounts();
            if ( !empty($pushToCardAccounts) ) {
                foreach ($pushToCardAccounts as $account) {
                    $accounts[] = [
                        "account_type" => AllocationWithAccount::ACCOUNT_TYPE_PUSH_TO_CARD,
                        'account_number' => $account['tokenInfo']['paymentInstrument']['last4'] ?? $account['formatAccountNumber'],
                        "disbursement_id" =>  $account["payeeDisbursementAccountId"],
                        "allocation" => $account['allocation']
                    ];
                }
            }

        } catch (\Throwable $th) {
            logger("Kyck Payee Error: unable to get payee data", [
                'message' => $th->getMessage(),
                'trace' => $th->getTrace()
            ]);
        }
        return $accounts;

    }


    public function getAllocationDisbursementPair() {
        $accounts = $this->getAllocationByAccounts();
        return collect($accounts)->pluck('allocation', 'disbursement_id')->toArray();
    }


    public function mapUpdateAllocationDisbursementPair(array $account_allocation_map) {
        $allocations = [];
        foreach ($account_allocation_map as $account_id => $allocation) {
            $allocations[] = [
                "payeeDisbursementAccountId" => $account_id,
                "allocation" =>  (int) $allocation
            ];
        }
        return $allocations;
    }


    /**
     * Get allocation data by account from the payee data
     *
     * @return array
     */
    public function getAllocationByAccount(): array {
        foreach ($this->allocation as $account_type) {
            // Check allow
            if ( !empty($this->payee_data[$account_type]) ) {
                foreach ($this->payee_data[$account_type] as $account) {
                    $this->allocation[$account_type][] = [
                        "payeeDisbursementAccountId" => $account["payeeDisbursementAccountId"],
                        "allocation" => $account["allocation"],
                    ];
                }
            }

            // Remove allocation for accounts that does not exists
            // if ( $account_type != "payeeNcrPay360Account" && empty($this->allocation[$account_type]) ) {
            //     unset($this->allocation[$account_type]);
            // }

        }
        return $this->allocation;
    }


    /**
     * Update allocation data by account from the payee data
     *
     * @param Payee $payee
     * @return array
     */
    public function updateAllocationByAccount(string $account_type, ?int $acount_id=null, int $account_allocation=0, bool $override=true): array {
        foreach ($this->allocation as $account_type) {

            // Check allow
            if ( !empty($this->payee_data[$account_type]) ) {
                foreach ($this->payee_data[$account_type] as $account) {
                    if ( !empty($account_id) ) {
                        if ( $account["payeeDisbursementAccountId"] != $acount_id && $override) {
                            $account_allocation = 0;
                        } else {
                            $account_allocation = $account["allocation"];
                        }
                    } elseif ( $override ) {
                        $account_allocation = 0;
                    }
                    $this->allocation[$account_type][] = [
                        "payeeDisbursementAccountId" => $account["payeeDisbursementAccountId"],
                        "allocation" => $account_allocation
                    ];
                }
            }

            // Remove allocation for accounts that does not exists
            if ( $account_type != "payeeNcrPay360Account" && empty($allocation[$account_type]) ) {
                unset($this->allocation[$account_type]);
            }

        }

        return $this->allocation;
    }


}


?>
