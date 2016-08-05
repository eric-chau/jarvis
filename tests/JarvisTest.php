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
        $jarvis = new Jarvis(['container_provider' => []]);

        $this->assertFalse(isset($jarvis['fake_container_provider_called']));

        $jarvis = new Jarvis(['container_provider' => 'Jarvis\Tests\FakeContainerProvider']);

        $this->assertTrue(isset($jarvis['fake_container_provider_called']));
        $this->assertTrue($jarvis['is_request_already_defined']); // ensure that Jarvis container provider is called first
    }

    public function testAnalyzeCatchEveryExceptionAndConvertItToResponse()
    {
        $jarvis = new Jarvis();

        $jarvis['router']
            ->beginRoute()
                ->setHandler([new FakeController(), 'throwExceptionAction'])
            ->end()
        ;
        $response = $jarvis->run();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(sprintf('%s::%s', FakeController::class, 'throwExceptionAction'), $response->getContent());
    }

    public function testAnalayzeOnInvalidRouteReturnsResponseWithStatusCode404()
    {
        $jarvis = new Jarvis();

        $response = $jarvis->run();
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testAnalyzeRouteWithWrongMethodReturnsResponseWithStatusCode405()
    {
        $jarvis = new Jarvis();

        $jarvis['router']
            ->beginRoute()
                ->setMethod('post')
                ->setHandler([new FakeController(), 'throwExceptionAction'])
            ->end()
        ;
        $response = $jarvis->run();

        $this->assertSame(405, $response->getStatusCode());
    }

    public function testAnalyzeExecutionIsStoppedIfRunEventHasResponseSetted()
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

    public function testReceiveResponseEventOnAnalyzeAndModifyResponseWillModifyReturnedResponse()
    {
        $jarvis = new Jarvis();

        $receiver = new FakeReceiver();
        $controller = new FakeController();

        $jarvis->on(BroadcasterInterface::RESPONSE_EVENT, [$receiver, 'modifyResponseOnResponseEvent']);

        $jarvis['router']
            ->beginRoute()
                ->setHandler([$controller, 'getFooAction'])
            ->end()
        ;

        $this->assertNull($receiver->responseEvent);

        $response = $jarvis->run();

        $this->assertNotSame($controller->getFooAction()->getContent(), $response->getContent());
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
        $jarvis = new Jarvis(['foo' => 'bar']);

        $this->assertTrue(isset($jarvis['settings']));
        $this->assertInstanceOf(ParameterBag::class, $jarvis['settings']);
        $this->assertTrue($jarvis['settings']->has('foo'));
        $this->assertSame('bar', $jarvis['settings']->get('foo'));
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

    public function testAnalyzeWillConvertToSymfonyResponseIfRouteCallbackReturnString()
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

        $result = $jarvis->run();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame($str, $result->getContent());
    }

    public function testBroadcastTerminateEventOnDestruct()
    {
        $jarvis = new Jarvis();

        $receiver = new FakeReceiver();
        $jarvis->on(BroadcasterInterface::TERMINATE_EVENT, [$receiver, 'onEventBroadcast']);

        $this->assertNull($receiver->event);

        unset($jarvis);
        $this->assertNotNull($receiver->event);
        $this->assertInstanceOf(SimpleEvent::class, $receiver->event);
    }

    public function testSessionService()
    {
        $jarvis = new Jarvis();

        $this->assertSame($jarvis->request->getSession(), $jarvis->session);
    }
}
