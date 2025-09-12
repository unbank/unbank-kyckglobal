<?php

namespace Unbank\Kyckglobal\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KyckTransactionError
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public $transaction;
    public $message;
    public $context;
    public $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Transaction $transaction, string $message, ...$context)
    {
        $this->transaction = $transaction;
        $this->user = $this->transaction?->user;
        $this->message = $message;
        $this->context = $context;
    }

}
