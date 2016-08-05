<?php

namespace Jarvis\Tests;

use Jarvis\Skill\EventBroadcaster\ControllerEvent;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class ControllerEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException        \TypeError
     */
    public function testSetInvalidCallbackRaiseException()
    {
        $event = new ControllerEvent(function() {});

        $this->assertSame($event, $event->setCallback('rand'));
        $event->setCallback(\Closure::fromCallable('foobar'));
    }
}
