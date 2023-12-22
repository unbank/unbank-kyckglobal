<?php

namespace Unbank\Kyckglobal\Facades;

use Illuminate\Support\Facades\Facade;

class KyckGlobal extends Facade {


    const TRANSACTION_DISBURSEMENT_STATUSES = [
        'Proccessing', "Submitted", "sent", 'Pickup Ready', 'Success'
    ];

    protected static function getFacadeAccessor() {
        return 'kyckglobal';
    }

}

?>
