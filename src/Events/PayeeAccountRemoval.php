<?php

namespace Unbank\Kyckglobal\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PayeeAccountRemoval
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public $user;
    public $context;
    public $account_type;
    public $reason;

     /**
     * Create a new event instance.
      *
      * @param \App\Models\User $user
      * @param mixed ...$context
      */
    public function __construct($user, $account_type, $reason='', ...$context)
    {
        $this->user = $user;
        $this->account_type = $account_type;
        $this->reason = $reason;
        $this->context = $context;
    }

}
