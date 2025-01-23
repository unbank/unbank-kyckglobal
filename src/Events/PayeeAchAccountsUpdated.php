<?php

namespace Unbank\Kyckglobal\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PayeeAchAccountsUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $payee;
    public $context;

    /**
     * Create a new event instance.
     *
     * @param $user
     * @param $payee
     * @param ...$context
     */
    public function __construct($user, $payee, ...$context)
    {
        $this->user = $user;
        $this->payee = $payee;
        $this->context = $context;
    }

}
