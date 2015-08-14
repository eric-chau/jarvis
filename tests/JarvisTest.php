<?php

namespace Jarvis\Tests;

use Jarvis\Jarvis;
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
