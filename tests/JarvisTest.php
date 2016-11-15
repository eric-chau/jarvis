<?php

namespace Jarvis\Tests;

use Jarvis\Jarvis;
use Jarvis\Skill\EventBroadcaster\BroadcasterInterface;
use Jarvis\Skill\EventBroadcaster\RunEvent;
use Jarvis\Skill\EventBroadcaster\SimpleEvent;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class JarvisTest extends \PHPUnit_Framework_TestCase
{
    public function testJarvisDebugVariable()
    {
        // By default Jarvis is started with debug setted to false.
        $app = new Jarvis();

        $this->assertFalse($app['debug']);

        // You can change the debug value by passing your debug value in settings array.
        $app = new Jarvis(['debug' => true]);

        $this->assertTrue($app['debug']);
    }

    public function testWithCustomContainerProvider()
    {
        $app = new Jarvis(['providers' => []]);

        $this->assertFalse(isset($app['fake_container_provider_called']));

        $app = new Jarvis(['providers' => 'Jarvis\Tests\FakeContainerProvider']);

        $this->assertTrue(isset($app['fake_container_provider_called']));
        // ensure that Jarvis container provider is called first
        $this->assertTrue($app['is_request_already_defined']);
    }

    public function testRunCatchEveryExceptionAndConvertItToResponse()
    {
        $app = new Jarvis(['debug' => true]);

        $app['router']
            ->beginRoute()
                ->setHandler(function () {
                    throw new \Exception('Hello, world!');
                })
            ->end()
        ;

        $this->assertInstanceOf(Response::class, $response = $app->run());
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(sprintf(
            '[Exception] error in %s at line 51 with message: %s',
            __FILE__,
            'Hello, world!'
        ), $response->getContent());
    }

    public function testRunOnInvalidRouteReturnsResponseWithStatusCode404()
    {
        $app = new Jarvis();

        $this->assertSame(404, $app->run()->getStatusCode());
    }

    public function testRunRouteWithWrongMethodReturnsResponseWithStatusCode405()
    {
        $app = new Jarvis();

        $app['router']
            ->beginRoute()
                ->setMethod('post')
                ->setHandler(function () {
                    return 'Hello, world!';
                })
            ->end()
        ;

        $this->assertSame(405, $app->run()->getStatusCode());
    }

    public function testRunExecutionIsStoppedIfRunEventHasResponseSetted()
    {
        $app = new Jarvis();

        $receiver = new FakeReceiver();

        $app->on(BroadcasterInterface::RUN_EVENT, [$receiver, 'onRunEventSetResponse']);
        $app->on(BroadcasterInterface::CONTROLLER_EVENT, [$receiver, 'onControllerEvent']);
        $app->on(BroadcasterInterface::RESPONSE_EVENT, [$receiver, 'onResponseEvent']);

        $this->assertNull($receiver->runEvent);
        $this->assertNull($receiver->controllerEvent);
        $this->assertNull($receiver->responseEvent);

        $response = $app->run();

        $this->assertSame(Response::HTTP_NOT_MODIFIED, $response->getStatusCode());
        $this->assertInstanceOf(RunEvent::class, $receiver->runEvent);
        $this->assertNull($receiver->controllerEvent);
        $this->assertNull($receiver->responseEvent);
    }

    public function testReceiveResponseEventOnRunAndModifyResponseWillModifyReturnedResponse()
    {
        $app = new Jarvis();

        $receiver = new FakeReceiver();
        $controller = function () {
            return new Response('foo');
        };

        $app->on(BroadcasterInterface::RESPONSE_EVENT, [$receiver, 'modifyResponseOnResponseEvent']);

        $app['router']
            ->beginRoute()
                ->setHandler($controller)
            ->end()
        ;

        $this->assertNull($receiver->responseEvent);

        $response = $app->run();

        $this->assertNotNull($receiver->responseEvent);
        $this->assertNotSame($controller()->getContent(), $response->getContent());
        $this->assertSame('bar', $response->getContent());

    }

    public function testRequestService()
    {
        $app = new Jarvis();

        $this->assertTrue(isset($app['request']));
        $this->assertInstanceOf(Request::class, $app['request']);
    }

    public function testSettingsService()
    {
        $app = new Jarvis([
            'extra' => [
                'foo' => 'bar',
            ],
        ]);

        $this->assertTrue(isset($app['foo.settings']));
        $this->assertSame('bar', $app['foo.settings']);
    }

    public function testAccessToLockedValueAsJarvisAttribute()
    {
        $app = new Jarvis();

        $app['foo'] = 'bar';
        $app->lock('foo');

        $this->assertSame('bar', $app->foo);
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage "foo" is not a key of a locked value.
     */
    public function testAccessToUnlockedValueOrNonExistentValueAsJarvisAttributeRaiseException()
    {
        $app = new Jarvis();

        $app['foo'] = 'bar';
        $app->foo;
    }

    /**
     * @expectedException        \LogicException
     * @expectedExceptionMessage You are not allowed to set new attribute into Jarvis.
     */
    public function testSetNewAttributeToJarvisWillRaiseException()
    {
        $app = new Jarvis();

        $app->foo = 'bar';
    }

    public function testRunWillConvertToSymfonyResponseIfRouteCallbackReturnString()
    {
        $app = new Jarvis();

        $str = 'hello world';

        $app['router']
            ->beginRoute()
                ->setHandler(function () use ($str) {
                    return $str;
                })
            ->end()
        ;

        $this->assertInstanceOf(Response::class, $response = $app->run());
        $this->assertSame($str, $response->getContent());
    }

    public function testBroadcastTerminateEventOnDestruct()
    {
        $app = new Jarvis();

        $receiver = new FakeReceiver();
        $app->on(BroadcasterInterface::TERMINATE_EVENT, [$receiver, 'onEventBroadcast']);

        $this->assertNull($receiver->event);

        unset($app);
        gc_collect_cycles();

        $this->assertNotNull($receiver->event);
        $this->assertInstanceOf(SimpleEvent::class, $receiver->event);
    }

    public function testSessionService()
    {
        $app = new Jarvis();

        $this->assertSame($app['request']->getSession(), $app['session']);
    }
}
