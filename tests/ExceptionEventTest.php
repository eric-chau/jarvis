<?php

use Jarvis\Skill\EventBroadcaster\ExceptionEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class ExceptionEventTest extends TestCase
{
    public function test_stopPropagation()
    {
        $event = new ExceptionEvent(new \Exception());

        $event->stopPropagation();
        $this->assertFalse($event->isPropagationStopped());

        $event->setResponse(new Response());
        $this->assertTrue($event->isPropagationStopped());
    }
}
