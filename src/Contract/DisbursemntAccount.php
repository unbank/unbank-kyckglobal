<?php

namespace Unbank\Kyckglobal\Contract;


interface DisbursemntAccount {


    /**
     * Get kyck disbursemnt account type
     */
    public function getKyckDisbursemntAccountType(): string;


    /**
     * Get kyck disbursement account identifier
     */
    public function getKyckDisbursemntAccountReference();


    /**
     * Get Kyck allocation
     * @uses \Unbank\Kyckglobal\AllocationWithAccount
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function kyckAccountAllocation(): \Illuminate\Database\Eloquent\Relations\MorphOne;


}


?>
