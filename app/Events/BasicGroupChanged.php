<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Management\BasicGroup;

class BasicGroupChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $basicGroup;
    public $action;

    /**
     * Create a new event instance.
     */
    public function __construct(BasicGroup $basicGroup, string $action)
    {
        $this->basicGroup = $basicGroup;
        $this->action = $action; // create, update, delete, restore, force_delete
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
