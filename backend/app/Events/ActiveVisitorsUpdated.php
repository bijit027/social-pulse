<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActiveVisitorsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $websiteId;
    public $activeVisitors;

    /**
     * Create a new event instance.
     */
    public function __construct($websiteId, $activeVisitors)
    {
        $this->websiteId = $websiteId;
        $this->activeVisitors = $activeVisitors;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('website.' . $this->websiteId . '.analytics'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'active_visitors' => $this->activeVisitors,
        ];
    }
}
