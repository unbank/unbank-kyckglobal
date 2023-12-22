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

}
