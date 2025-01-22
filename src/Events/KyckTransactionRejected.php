<?php

namespace Unbank\Kyckglobal\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KyckTransactionRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transaction;
    public array $context;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($transaction, ...$context)
    {
        $this->transaction = $transaction;
        $this->context = $context;
    }

}
