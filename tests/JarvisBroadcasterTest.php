<?php

namespace Jarvis\Tests;

use Jarvis\Jarvis;
use Jarvis\Skill\DependencyInjection\Reference;
use Jarvis\Skill\EventBroadcaster\AnalyzeEvent;
use Jarvis\Skill\EventBroadcaster\ControllerEvent;
use Jarvis\Skill\EventBroadcaster\EventInterface;
use Jarvis\Skill\EventBroadcaster\JarvisEvents;
use Jarvis\Skill\EventBroadcaster\SimpleEvent;
use Jarvis\Skill\EventBroadcaster\ResponseEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests for Jarvis broadcast message skill.
 *
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class JarvisBroadcasterTest extends \PHPUnit_Framework_TestCase
{
    const RANDOM_EVENT_NAME = 'random.event_name';

    public function testReceiverAlwaysGetAnInstanceOfEventInterfaceAsFirstArgument()
    {
        $jarvis = new Jarvis();

        $receiver = new FakeReceiver();

        $jarvis->addReceiver(self::RANDOM_EVENT_NAME, [$receiver, 'onEventBroadcast']);

        $this->assertNull($receiver->event);

        $jarvis->broadcast(self::RANDOM_EVENT_NAME);

        $this->assertInstanceOf(EventInterface::class, $receiver->event);
        $this->assertInstanceOf(SimpleEvent::class, $receiver->event);
    }

    public function testBroadcastOfAnalyzeEventAndControllerEventAndResponseEventDuringAnalyzeExecution()
    {
        $jarvis = new Jarvis();

        $receiver = new FakeReceiver();

        $jarvis->addReceiver(JarvisEvents::ANALYZE_EVENT, [$receiver, 'onAnalyzeEvent']);
        $jarvis->addReceiver(JarvisEvents::CONTROLLER_EVENT, [$receiver, 'onControllerEvent']);
        $jarvis->addReceiver(JarvisEvents::RESPONSE_EVENT, [$receiver, 'onResponseEvent']);

        $jarvis['fake_controller'] = function () {
            return new FakeController();
        };

        $jarvis['router']->addRoute('GET', '/', [new Reference('fake_controller'), 'randomAction']);

        $this->assertNull($receiver->analyzeEvent);
        $this->assertNull($receiver->controllerEvent);
        $this->assertNull($receiver->responseEvent);

        $response = $jarvis->analyze(new Request());

        $this->assertInstanceOf(AnalyzeEvent::class, $receiver->analyzeEvent);
        $this->assertInstanceOf(ControllerEvent::class, $receiver->controllerEvent);
        $this->assertInstanceOf(ResponseEvent::class, $receiver->responseEvent);
    }

    public function testBroadcastEventArgumentWillAlwaysBePassedToReceivers()
    {
        $jarvis = new Jarvis();

        $receiver = new FakeReceiver();

        $this->assertNotInstanceOf(FakeEvent::class, $receiver->event);
        $jarvis->addReceiver(self::RANDOM_EVENT_NAME, [$receiver, 'onEventBroadcast']);
        $jarvis->broadcast(self::RANDOM_EVENT_NAME, new FakeEvent());
        $this->assertInstanceOf(FakeEvent::class, $receiver->event);
    }

    /**
     * @expectedException \LogicException
     */
    public function testExceptionOnUnauthorizedBroadcastAnalyzeEvent()
    {
        $jarvis = new Jarvis();
        $jarvis->broadcast(JarvisEvents::ANALYZE_EVENT);
    }

    /**
     * @expectedException \LogicException
     */
    public function testExceptionOnUnauthorizedBroadcastControllerEvent()
    {
        $jarvis = new Jarvis();
        $jarvis->broadcast(JarvisEvents::CONTROLLER_EVENT);
    }

    /**
     * @expectedException \LogicException
     */
    public function testExceptionOnUnauthorizedBroadcastResponseEvent()
    {
        $jarvis = new Jarvis();
        $jarvis->broadcast(JarvisEvents::RESPONSE_EVENT);
    }

    /**
     * @expectedException \LogicException
     */
    public function testExceptionOnUnauthorizedBroadcastExceptionEvent()
    {
        $jarvis = new Jarvis();
        $jarvis->broadcast(JarvisEvents::EXCEPTION_EVENT);
    }
}
