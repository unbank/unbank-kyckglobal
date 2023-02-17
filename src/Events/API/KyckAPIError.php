<?php

namespace Unbank\Kyckglobal\Events\API;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KyckAPIError
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $context;

     /**
     * Create a new event instance.
      *
      * @param \App\Models\User $user
      * @param mixed ...$context
      */
    public function __construct(
        public $request=[],
        public $response=[],
        ...$context
    )
    {
        $this->context = $context;
    }

}
