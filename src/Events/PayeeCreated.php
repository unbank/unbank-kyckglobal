<?php

namespace Unbank\Kyckglobal\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PayeeCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public $user;
    public $payee;

     /**
     * Create a new event instance.
      *
      * @param \App\Models\User $user
      * @param mixed ...$context
      */
    public function __construct($user, $payee)
    {
        $this->user = $user;
        $this->payee = $payee;
    }

}
