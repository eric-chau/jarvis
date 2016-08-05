<?php

namespace Jarvis\Tests;

use Jarvis\Jarvis;
use Jarvis\Skill\DependencyInjection\Reference;
use Jarvis\Skill\EventBroadcaster\RunEvent;
use Jarvis\Skill\EventBroadcaster\ControllerEvent;
use Jarvis\Skill\EventBroadcaster\EventInterface;
use Jarvis\Skill\EventBroadcaster\BroadcasterInterface;
use Jarvis\Skill\EventBroadcaster\PermanentEvent;
use Jarvis\Skill\EventBroadcaster\SimpleEvent;
use Jarvis\Skill\EventBroadcaster\ResponseEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests for Jarvis broadcast message skill.
 *
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class BroadcasterInterfaceTest extends \PHPUnit_Framework_TestCase
{
    const RANDOM_EVENT_NAME = 'random.event_name';

    public function testReceiverAlwaysGetAnInstanceOfEventInterfaceAsFirstArgument()
    {
        $jarvis = new Jarvis();

        $receiver = new FakeReceiver();

        $jarvis->on(self::RANDOM_EVENT_NAME, [$receiver, 'onEventBroadcast']);

        $this->assertNull($receiver->event);

        $jarvis->broadcast(self::RANDOM_EVENT_NAME);

        $this->assertInstanceOf(EventInterface::class, $receiver->event);
        $this->assertInstanceOf(SimpleEvent::class, $receiver->event);
    }

    public function testBroadcastOfRunEventAndControllerEventAndResponseEventDuringAnalyzeExecution()
    {
        $jarvis = new Jarvis();

        $receiver = new FakeReceiver();

        $jarvis->on(BroadcasterInterface::RUN_EVENT, [$receiver, 'onRunEvent']);
        $jarvis->on(BroadcasterInterface::CONTROLLER_EVENT, [$receiver, 'onControllerEvent']);
        $jarvis->on(BroadcasterInterface::RESPONSE_EVENT, [$receiver, 'onResponseEvent']);

        $jarvis['fake_controller'] = function () {
            return new FakeController();
        };

        $jarvis['router']
            ->beginRoute()
                ->setHandler([new Reference('fake_controller'), 'randomAction'])
            ->end()
        ;

        $this->assertNull($receiver->runEvent);
        $this->assertNull($receiver->controllerEvent);
        $this->assertNull($receiver->responseEvent);

        $response = $jarvis->run(new Request());

        $this->assertInstanceOf(RunEvent::class, $receiver->runEvent);
        $this->assertInstanceOf(ControllerEvent::class, $receiver->controllerEvent);
        $this->assertInstanceOf(ResponseEvent::class, $receiver->responseEvent);
    }

    public function testBroadcastEventArgumentWillAlwaysBePassedToReceivers()
    {
        $jarvis = new Jarvis();

        $receiver = new FakeReceiver();

        $this->assertNotInstanceOf(FakeEvent::class, $receiver->event);
        $jarvis->on(self::RANDOM_EVENT_NAME, [$receiver, 'onEventBroadcast']);
        $jarvis->broadcast(self::RANDOM_EVENT_NAME, new FakeEvent());
        $this->assertInstanceOf(FakeEvent::class, $receiver->event);
    }

    /**
     * @expectedException \LogicException
     */
    public function testExceptionOnUnauthorizedBroadcastRunEvent()
    {
        $jarvis = new Jarvis();
        $jarvis->broadcast(BroadcasterInterface::RUN_EVENT);
    }

    /**
     * @expectedException \LogicException
     */
    public function testExceptionOnUnauthorizedBroadcastControllerEvent()
    {
        $jarvis = new Jarvis();
        $jarvis->broadcast(BroadcasterInterface::CONTROLLER_EVENT);
    }

    /**
     * @expectedException \LogicException
     */
    public function testExceptionOnUnauthorizedBroadcastResponseEvent()
    {
        $jarvis = new Jarvis();
        $jarvis->broadcast(BroadcasterInterface::RESPONSE_EVENT);
    }

    /**
     * @expectedException \LogicException
     */
    public function testExceptionOnUnauthorizedBroadcastExceptionEvent()
    {
        $jarvis = new Jarvis();
        $jarvis->broadcast(BroadcasterInterface::EXCEPTION_EVENT);
    }

    public function testEventReceiversPriorities()
    {
        $jarvis = new Jarvis();

        $eventName = 'receiver_priority_test';

        $lowPriorityReceiver = new FakeReceiver();
        $normalPriorityReceiver = new FakeReceiver();
        $highPriorityReceiver = new FakeReceiver();

        $this->assertNull($lowPriorityReceiver->microTimestamp);
        $this->assertNull($normalPriorityReceiver->microTimestamp);
        $this->assertNull($highPriorityReceiver->microTimestamp);

        $jarvis->on($eventName, [$lowPriorityReceiver, 'saveMicroTimestamp'], Jarvis::RECEIVER_LOW_PRIORITY);
        $jarvis->on($eventName, [$normalPriorityReceiver, 'saveMicroTimestamp'], Jarvis::RECEIVER_NORMAL_PRIORITY);
        $jarvis->on($eventName, [$highPriorityReceiver, 'saveMicroTimestamp'], Jarvis::RECEIVER_HIGH_PRIORITY);

        $jarvis->broadcast($eventName);

        $this->assertTrue($normalPriorityReceiver->microTimestamp < $lowPriorityReceiver->microTimestamp);
        $this->assertTrue($highPriorityReceiver->microTimestamp < $normalPriorityReceiver->microTimestamp);
    }

    public function testPermanentEvent()
    {
        $jarvis = new Jarvis();

        $receiver = new FakeReceiver();

        $eventName = 'permanent.event.init';
        $jarvis->on($eventName, [$receiver, 'onEventBroadcast']);
        $this->assertNull($receiver->event);

        $event = new SimpleEvent();
        $permanentEvent = new PermanentEvent();
        $jarvis->broadcast($eventName, $event);
        $this->assertSame($event, $receiver->event);

        $jarvis->broadcast($eventName, $permanentEvent);
        $jarvis->on($eventName, [$receiver, 'onEventBroadcast']);
        $this->assertNotSame($event, $receiver->event);
        $this->assertSame($permanentEvent, $receiver->event);
    }
}
