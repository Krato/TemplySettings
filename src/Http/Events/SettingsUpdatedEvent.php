<?php

namespace Infinety\TemplySettings\Http\Events;

use Illuminate\Queue\SerializesModels;

class SettingsUpdatedEvent
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  \App\Order  $order
     * @return void
     */
    public function __construct()
    {
        //
    }
}
