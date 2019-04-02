<?php

namespace NotificationChannels\PusherPushNotifications\Test;

use Illuminate\Events\Dispatcher;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\PusherPushNotifications\PusherChannel;
use Illuminate\Notifications\Notification;
use NotificationChannels\PusherPushNotifications\PusherMessage;
use Mockery;
use PHPUnit\Framework\TestCase;
use Pusher\PushNotifications\PushNotifications;

class ChannelTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function setUp()
    {
        $this->pusher = Mockery::mock(PushNotifications::class);

        $this->events = Mockery::mock(Dispatcher::class);

        $this->channel = new PusherChannel($this->pusher, $this->events);

        $this->notification = new TestNotification;

        $this->notifiable = new TestNotifiable;
    }

    public function tearDown()
    {
        Mockery::close();

        parent::tearDown();
    }

    /** @test */
    public function it_can_send_a_notification()
    {
        $message = $this->notification->toPushNotification($this->notifiable);

        $data = $message->toArray();

        $mockResponse = new \stdClass();
        $mockResponse->publishId = 'fake-id';

        $this->pusher->shouldReceive('publish')->with(['interest_name'], $data)->andReturn($mockResponse);

        $this->channel->send($this->notifiable, $this->notification);
    }

    /** @test */
    public function it_fires_failure_event_on_failure()
    {
        $message = $this->notification->toPushNotification($this->notifiable);

        $data = $message->toArray();

        $this->pusher->shouldReceive('publish')->with(['interest_name'], $data)->andThrow(new \Exception);

        $this->events->shouldReceive('fire')->with(Mockery::type(NotificationFailed::class));

        $this->channel->send($this->notifiable, $this->notification);
    }
}

class TestNotifiable
{
    use Notifiable;

    public function routeNotificationForPusherPushNotifications()
    {
        return 'interest_name';
    }
}

class TestNotification extends Notification
{
    public function toPushNotification($notifiable)
    {
        return new PusherMessage();
    }
}
