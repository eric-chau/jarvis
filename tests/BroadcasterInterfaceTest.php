<?php

namespace Jarvis\Tests;

use Jarvis\Jarvis;
use Jarvis\Skill\EventBroadcaster\RunEvent;
use Jarvis\Skill\EventBroadcaster\ControllerEvent;
use Jarvis\Skill\EventBroadcaster\EventInterface;
use Jarvis\Skill\EventBroadcaster\BroadcasterInterface;
use Jarvis\Skill\EventBroadcaster\PermanentEvent;
use Jarvis\Skill\EventBroadcaster\SimpleEvent;
use Jarvis\Skill\EventBroadcaster\ResponseEvent;

/**
 * Tests for Jarvis broadcast message skill.
 *
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class BroadcasterInterfaceTest extends \PHPUnit_Framework_TestCase
{
    const RANDOM_EVENT_NAME = 'random.event_name';

    public function test_receiver_get_instance_of_EventInterface_as_first_argument()
    {
        $app = new Jarvis();

        $receiver = new FakeReceiver();

        $app->on(self::RANDOM_EVENT_NAME, [$receiver, 'onEventBroadcast']);

        $this->assertNull($receiver->event);

        $app->broadcast(self::RANDOM_EVENT_NAME);

        $this->assertInstanceOf(EventInterface::class, $receiver->event);
        $this->assertInstanceOf(SimpleEvent::class, $receiver->event);
    }

    public function test_run_will_broadcast_RunEvent_ControllerEvent_And_ResponseEvent()
    {
        $app = new Jarvis();

        $receiver = new FakeReceiver();

        $app->on(BroadcasterInterface::RUN_EVENT, [$receiver, 'onRunEvent']);
        $app->on(BroadcasterInterface::CONTROLLER_EVENT, [$receiver, 'onControllerEvent']);
        $app->on(BroadcasterInterface::RESPONSE_EVENT, [$receiver, 'onResponseEvent']);

        $app['router']
            ->beginRoute()
                ->setHandler(function () {
                    return 'Hello, world!';
                })
            ->end()
        ;

        $this->assertNull($receiver->runEvent);
        $this->assertNull($receiver->controllerEvent);
        $this->assertNull($receiver->responseEvent);

        $response = $app->run();

        $this->assertInstanceOf(RunEvent::class, $receiver->runEvent);
        $this->assertInstanceOf(ControllerEvent::class, $receiver->controllerEvent);
        $this->assertInstanceOf(ResponseEvent::class, $receiver->responseEvent);
    }

    public function test_broadcast_event_is_passed_itself_to_receivers()
    {
        $app = new Jarvis();

        $receiver = new FakeReceiver();

        $this->assertNotInstanceOf(FakeEvent::class, $receiver->event);
        $app->on(self::RANDOM_EVENT_NAME, [$receiver, 'onEventBroadcast']);
        $app->broadcast(self::RANDOM_EVENT_NAME, new FakeEvent());
        $this->assertInstanceOf(FakeEvent::class, $receiver->event);
    }

    /**
     * @expectedException \LogicException
     */
    public function test_exception_on_broadcast_of_reserved_event_name()
    {
        $app = new Jarvis();
        $app->broadcast(BroadcasterInterface::RUN_EVENT);
    }

    public function test_event_receiver_priorities()
    {
        $app = new Jarvis();

        $eventName = 'receiver_priority_test';

        $lowPriorityReceiver = new FakeReceiver();
        $normalPriorityReceiver = new FakeReceiver();
        $highPriorityReceiver = new FakeReceiver();

        $this->assertNull($lowPriorityReceiver->microTimestamp);
        $this->assertNull($normalPriorityReceiver->microTimestamp);
        $this->assertNull($highPriorityReceiver->microTimestamp);

        $app->on($eventName, [$lowPriorityReceiver, 'saveMicroTimestamp'], Jarvis::RECEIVER_LOW_PRIORITY);
        $app->on($eventName, [$normalPriorityReceiver, 'saveMicroTimestamp'], Jarvis::RECEIVER_NORMAL_PRIORITY);
        $app->on($eventName, [$highPriorityReceiver, 'saveMicroTimestamp'], Jarvis::RECEIVER_HIGH_PRIORITY);

        $app->broadcast($eventName);

        $this->assertTrue($normalPriorityReceiver->microTimestamp < $lowPriorityReceiver->microTimestamp);
        $this->assertTrue($highPriorityReceiver->microTimestamp < $normalPriorityReceiver->microTimestamp);
    }

    public function test_permanent_event()
    {
        $app = new Jarvis();
        $receiver = new FakeReceiver();

        $name = 'classic.event';
        $simpleEvent = new SimpleEvent();

        $this->assertNull($receiver->event);
        $app->broadcast($name, $simpleEvent);
        $app->on($name, [$receiver, 'onEventBroadcast']);
        $this->assertNull($receiver->event);

        $name = 'permanent.event';
        $permanentEvent = new PermanentEvent();

        $app->broadcast($name, $permanentEvent);
        $app->on($name, [$receiver, 'onEventBroadcast']);
        $this->assertSame($permanentEvent, $receiver->event);
    }

    /**
     * @expectedException \LogicException
     * @expectedMessageException Permanent event cannot be broadcasted multiple times.
     */
    public function test_forbid_multiple_broadcast_of_permanent_event()
    {
        $app = new Jarvis();
        $permanentEvent = new PermanentEvent();

        $name = 'permanent.event';
        $app->broadcast($name, $permanentEvent);
        $app->broadcast($name, $permanentEvent);
    }
}
