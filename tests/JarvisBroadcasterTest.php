<?php

use Jarvis\Jarvis;
use Jarvis\Event\JarvisEvents;

/**
 * Tests for Jarvis broadcast message skill.
 *
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class JarvisBroadcasterTest extends \PHPUnit_Framework_TestCase
{
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
