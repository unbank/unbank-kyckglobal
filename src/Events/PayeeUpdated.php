<?php

namespace Unbank\Kyckglobal\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PayeeUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public $user;
    public $payee;
    public $context;

     /**
     * Create a new event instance.
      *
      * @param \App\Models\User $user
      * @param mixed ...$context
      */
    public function __construct($user, $payee, ...$context)
    {
        $this->user = $user;
        $this->payee = $payee;
        $this->context = $context;
    }

}
