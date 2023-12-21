<?php

namespace Unbank\Kyckglobal\Helpers;

use Unbank\Kyckglobal\Payee;

class AllocationDataHelper {

    protected array $allocation = [
        "payeeNcrPay360Account" => [],
        "payeePaypalAccount" => []
    ];
    protected array $payee_data;


    public function __construct(protected Payee $payee)
    {
        $this->payee_data = $payee->data;
    }

    public function payee(): Payee {
        return $this->payee;
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
            if ( $account_type != "payeeNcrPay360Account" && empty($this->allocation[$account_type]) ) {
                unset($this->allocation[$account_type]);
            }

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
