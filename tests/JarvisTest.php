<?php

use Jarvis\Jarvis;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class JarvisTest extends PHPUnit_Framework_TestCase
{
    public function testRequestService()
    {
        $jarvis = new Jarvis();

        $this->assertTrue(isset($jarvis['request']));
        $this->assertInstanceOf(Request::class, $jarvis['request']);
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage Expect every container provider to implement Jarvis\DependencyInjection\ContainerProviderInterface
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
