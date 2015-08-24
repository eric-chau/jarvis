<?php

namespace Jarvis\Tests;

use Jarvis\Jarvis;
use Jarvis\Skill\EventBroadcaster\AnalyzeEvent;
use Jarvis\Skill\EventBroadcaster\JarvisEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class JarvisTest extends \PHPUnit_Framework_TestCase
{
    public function testAnalyzeCatchEveryExceptionAndConvertItToResponse()
    {
        $jarvis = new Jarvis();

        $jarvis['router']->addRoute('get', '/', [new FakeController(), 'throwExceptionAction']);
        $response = $jarvis->analyze();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(sprintf('%s::%s', FakeController::class, 'throwExceptionAction'), $response->getContent());
    }

    public function testAnalayzeOnInvalidRouteReturnsResponseWithStatusCode404()
    {
        $jarvis = new Jarvis();

        $response = $jarvis->analyze();
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testAnalyzeRouteWithWrongMethodReturnsResponseWithStatusCode405()
    {
        $jarvis = new Jarvis();

        $jarvis['router']->addRoute('post', '/', [new FakeController(), 'throwExceptionAction']);
        $response = $jarvis->analyze();

        $this->assertSame(405, $response->getStatusCode());
    }

    public function testAnalyzeExecutionIsStoppedIfAnalyzeEventHasResponseSetted()
    {
        $jarvis = new Jarvis();

        $receiver = new FakeReceiver();

        $jarvis->addReceiver(JarvisEvents::ANALYZE_EVENT, [$receiver, 'onAnalyzeEventSetResponse']);
        $jarvis->addReceiver(JarvisEvents::CONTROLLER_EVENT, [$receiver, 'onControllerEvent']);
        $jarvis->addReceiver(JarvisEvents::RESPONSE_EVENT, [$receiver, 'onResponseEvent']);

        $this->assertNull($receiver->analyzeEvent);
        $this->assertNull($receiver->controllerEvent);
        $this->assertNull($receiver->responseEvent);

        $response = $jarvis->analyze();

        $this->assertSame(Response::HTTP_NOT_MODIFIED, $response->getStatusCode());
        $this->assertInstanceOf(AnalyzeEvent::class, $receiver->analyzeEvent);
        $this->assertNull($receiver->controllerEvent);
        $this->assertNull($receiver->responseEvent);
    }

    public function testRequestService()
    {
        $jarvis = new Jarvis();

        $this->assertTrue(isset($jarvis['request']));
        $this->assertInstanceOf(Request::class, $jarvis['request']);
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage Expect every container provider to implement Jarvis\Skill\DependencyInjection\ContainerProviderInterface
     */
    public function testInvalidContainerProvider()
    {
        new Jarvis([
            'container_provider' => [
                'stdClass',
            ],
        ]);
    }
}
