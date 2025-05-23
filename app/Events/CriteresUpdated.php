<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class CriteresUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Les critères mis à jour
     *
     * @var Collection
     */
    public $criteres;

    /**
     * Create a new event instance.
     *
     * @param  Collection  $criteres
     * @return void
     */
    public function __construct($criteres)
    {
        $this->criteres = $criteres;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('criteres-pointage');
    }
}
