<?php

namespace Acelle\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Acelle\Model\Subscriber;

class SubscribersAttributesUpdate
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $subscriber;
    public $changes;
    public $oldAttributes;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Subscriber $subscriber, array $changes, array $oldAttributes)
    {
        $this->subscriber = $subscriber;
        $this->changes = $changes;
        $this->oldAttributes = $oldAttributes;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
