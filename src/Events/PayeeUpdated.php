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
    public $context;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($user, $context=null)
    {
        $this->user = $user;
        $this->context = $context;
    }

}
