<?php

namespace Jarvis\Tests;

use Jarvis\Jarvis;
use Jarvis\Skill\EventBroadcaster\ControllerEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class ControllerEventTest extends TestCase
{
    public function test_controller_smart_type_hint()
    {
        $app = new Jarvis(['debug' => true]);

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
