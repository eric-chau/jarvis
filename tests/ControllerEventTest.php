<?php

namespace Jarvis\Tests;

use Jarvis\Jarvis;
use Jarvis\Skill\EventBroadcaster\ControllerEvent;
use Symfony\Component\HttpFoundation\Response;

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
        $event = new ControllerEvent(function () {});

        $this->assertSame($event, $event->setCallback('rand'));
        $event->setCallback(\Closure::fromCallable('foobar'));
    }

    public function testControllerSmartTypeHint()
    {
        $app = new Jarvis();

        $app['router']
            ->beginRoute()
                ->setHandler(function (\DateTime $datetime) {
                    return str_replace(':', 'h', $datetime->format('d/m/Y H:i'));
                })
            ->end()
        ;

        $response = $app->run();
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());

        $app['current_datetime'] = function () {
            return new \DateTime('1988-06-08 00:00:00');
        };

        $response = $app->run();
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());

        $app['current_datetime'] = function (): \DateTime {
            return new \DateTime('1988-06-08 00:00:00');
        };

        $response = $app->run();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('08/06/1988 00h00', $response->getContent());
        $this->assertTrue(isset($app['DateTime']));
    }
}
