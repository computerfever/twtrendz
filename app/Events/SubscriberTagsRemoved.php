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

class SubscriberTagsRemoved
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $subscriber;
    public $removedTags;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Subscriber $subscriber, $removedTags)
    {
        $this->subscriber = $subscriber;
        $this->removedTags = $removedTags;
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
