<?php

namespace Unbank\Kyckglobal\Traits;

use Unbank\Kyckglobal\AllocationWithAccount;

trait HasKyckAccountAllocation {

    /**
     * Get Kyck allocation
     * @uses \Unbank\Kyckglobal\AllocationWithAccount
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function kyckAccountAllocation(): \Illuminate\Database\Eloquent\Relations\MorphOne {
        return $this->morphOne(AllocationWithAccount::class, 'disbursable');
    }


    /**
     * Disbursements
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function disbursements(): \Illuminate\Database\Eloquent\Relations\MorphMany  {
        return $this->morphMany(config('kyckglobal.disbursement_class'), 'disbursable');
    }


    /**
     * Get kyck_disbursement_account_id
     *
     * @return null|integer
     */
    public function getKyckDisbursementAccountId(): ?int {
        if ( empty($this->kyckAccountAllocation) ) {
            return null;
        }
        return $this->kyckAccountAllocation->account_id;
    }

    /**
     * Get kyck_disbursement_account_id
     *
     * @return null|integer
     */
    public function getKyckDisbursementAccountAllocation(): ?int {
        if ( empty($this->kyckAccountAllocation) ) {
            return null;
        }
        return $this->kyckAccountAllocation->allocation;
    }

}
