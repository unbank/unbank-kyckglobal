<?php

namespace Unbank\Kyckglobal\Facades;

use Illuminate\Support\Facades\Facade;

class KyckGlobal extends Facade {

    protected static function getFacadeAccessor() {
        return 'kyckglobal';
    }
}

?>
