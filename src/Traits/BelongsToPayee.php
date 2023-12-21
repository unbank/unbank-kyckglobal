<?php

namespace Unbank\Kyckglobal\Traits;

use Unbank\Kyckglobal\Payee;

trait BelongsToPayee {

    /**
     * Get the user that owns the BelongsToUser
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function payee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Payee::class, 'payee_id');
    }

}
