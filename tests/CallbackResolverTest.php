<?php

namespace Jarvis\Tests;

use Jarvis\Jarvis;
use Jarvis\Skill\DependencyInjection\Reference;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class CallbackResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testReferenceIsReplacedByValueFromController()
    {
        $jarvis = new Jarvis();

        $jarvis['datetime'] = new \DateTime();

        $callback = [new Reference('datetime'), 'getTimestamp'];

        $this->assertNotInstanceOf(\Closure::class, $callback);
        $callback = $jarvis->callbackResolver->resolve($callback);
        $this->assertInstanceOf(\Closure::class, $callback);
    }

    public function testResolveAcceptAnyCallableCallback()
    {
        $jarvis = new Jarvis();

        try {
            $jarvis->callbackResolver->resolve([new \DateTime(), 'getTimestamp']);
            $jarvis->callbackResolver->resolve(['DateTime', 'createFromFormat']);
            $jarvis->callbackResolver->resolve('rand');
            $jarvis->callbackResolver->resolve(function() {});
            $this->assertTrue(true);
        } catch (\InvalidArgumentException $e) {
            if ('Provided callback is not callable.' === $e->getMessage()) {
                $this->fail('Php valid callable must successfully be resolved by CallbackResolver.');
            }

            throw $e;
        }
    }

    /**
     * @expectedException        \TypeError
     */
    public function testResolveRaisesExceptionOnInvalidCallback()
    {
        $jarvis = new Jarvis();

        $jarvis->callbackResolver->resolve([new \DateTime(), 'hello']);
    }
}
