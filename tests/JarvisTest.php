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
        $jarvis = new Jarvis();

        $this->assertFalse($jarvis->debug);

        // You can change the debug value by passing your debug value in settings array.
        $jarvis = new Jarvis(['debug' => true]);

        $this->assertTrue($jarvis->debug);
    }

    public function testWithCustomContainerProvider()
    {
        $jarvis = new Jarvis(['providers' => []]);

        $this->assertFalse(isset($jarvis['fake_container_provider_called']));

        $jarvis = new Jarvis(['providers' => 'Jarvis\Tests\FakeContainerProvider']);

        $this->assertTrue(isset($jarvis['fake_container_provider_called']));
        // ensure that Jarvis container provider is called first
        $this->assertTrue($jarvis['is_request_already_defined']);
    }

    public function testRunCatchEveryExceptionAndConvertItToResponse()
    {
        $jarvis = new Jarvis(['debug' => true]);

        $jarvis['router']
            ->beginRoute()
                ->setHandler(function () {
                    throw new \Exception('Hello, world!');
                })
            ->end()
        ;

        $this->assertInstanceOf(Response::class, $response = $jarvis->run());
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(sprintf(
            '[Exception] error in %s at line 51 with message: %s',
            __FILE__,
            'Hello, world!'
        ), $response->getContent());
    }

    public function testRunOnInvalidRouteReturnsResponseWithStatusCode404()
    {
        $jarvis = new Jarvis();

        $this->assertSame(404, $jarvis->run()->getStatusCode());
    }

    public function testRunRouteWithWrongMethodReturnsResponseWithStatusCode405()
    {
        $jarvis = new Jarvis();

        $jarvis['router']
            ->beginRoute()
                ->setMethod('post')
                ->setHandler(function () {
                    return 'Hello, world!';
                })
            ->end()
        ;

        $this->assertSame(405, $jarvis->run()->getStatusCode());
    }

    public function testRunExecutionIsStoppedIfRunEventHasResponseSetted()
    {
        $jarvis = new Jarvis();

        $receiver = new FakeReceiver();

        $jarvis->on(BroadcasterInterface::RUN_EVENT, [$receiver, 'onRunEventSetResponse']);
        $jarvis->on(BroadcasterInterface::CONTROLLER_EVENT, [$receiver, 'onControllerEvent']);
        $jarvis->on(BroadcasterInterface::RESPONSE_EVENT, [$receiver, 'onResponseEvent']);

        $this->assertNull($receiver->runEvent);
        $this->assertNull($receiver->controllerEvent);
        $this->assertNull($receiver->responseEvent);

        $response = $jarvis->run();

        $this->assertSame(Response::HTTP_NOT_MODIFIED, $response->getStatusCode());
        $this->assertInstanceOf(RunEvent::class, $receiver->runEvent);
        $this->assertNull($receiver->controllerEvent);
        $this->assertNull($receiver->responseEvent);
    }

    public function testReceiveResponseEventOnRunAndModifyResponseWillModifyReturnedResponse()
    {
        $jarvis = new Jarvis();

        $receiver = new FakeReceiver();
        $controller = function () {
            return new Response('foo');
        };

        $jarvis->on(BroadcasterInterface::RESPONSE_EVENT, [$receiver, 'modifyResponseOnResponseEvent']);

        $jarvis['router']
            ->beginRoute()
                ->setHandler($controller)
            ->end()
        ;

        $this->assertNull($receiver->responseEvent);

        $response = $jarvis->run();

        $this->assertNotNull($receiver->responseEvent);
        $this->assertNotSame($controller()->getContent(), $response->getContent());
        $this->assertSame('bar', $response->getContent());

    }

    public function testRequestService()
    {
        $jarvis = new Jarvis();

        $this->assertTrue(isset($jarvis['request']));
        $this->assertInstanceOf(Request::class, $jarvis['request']);
    }

    public function testSettingsService()
    {
        $jarvis = new Jarvis([
            'extra' => [
                'foo' => 'bar',
            ],
        ]);

        $this->assertTrue(isset($jarvis['foo.settings']));
        $this->assertSame('bar', $jarvis['foo.settings']);
    }

    public function testAccessToLockedValueAsJarvisAttribute()
    {
        $jarvis = new Jarvis();

        $jarvis['foo'] = 'bar';
        $jarvis->lock('foo');

        $this->assertSame('bar', $jarvis->foo);
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage "foo" is not a key of a locked value.
     */
    public function testAccessToUnlockedValueOrNonExistentValueAsJarvisAttributeRaiseException()
    {
        $jarvis = new Jarvis();

        $jarvis['foo'] = 'bar';
        $jarvis->foo;
    }

    /**
     * @expectedException        \LogicException
     * @expectedExceptionMessage You are not allowed to set new attribute into Jarvis.
     */
    public function testSetNewAttributeToJarvisWillRaiseException()
    {
        $jarvis = new Jarvis();

        $jarvis->foo = 'bar';
    }

    public function testRunWillConvertToSymfonyResponseIfRouteCallbackReturnString()
    {
        $jarvis = new Jarvis();

        $str = 'hello world';

        $jarvis->router
            ->beginRoute()
                ->setHandler(function () use ($str) {
                    return $str;
                })
            ->end()
        ;

        $this->assertInstanceOf(Response::class, $response = $jarvis->run());
        $this->assertSame($str, $response->getContent());
    }

    public function testBroadcastTerminateEventOnDestruct()
    {
        $jarvis = new Jarvis();

        $receiver = new FakeReceiver();
        $jarvis->on(BroadcasterInterface::TERMINATE_EVENT, [$receiver, 'onEventBroadcast']);

        $this->assertNull($receiver->event);

        unset($jarvis);
        gc_collect_cycles();

        $this->assertNotNull($receiver->event);
        $this->assertInstanceOf(SimpleEvent::class, $receiver->event);
    }

    public function testSessionService()
    {
        $jarvis = new Jarvis();

        $this->assertSame($jarvis->request->getSession(), $jarvis->session);
    }
}
