<?php

namespace Jarvis\Tests;

use Jarvis\Jarvis;
use Jarvis\Skill\DependencyInjection\Reference;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class CallbackResolver extends \PHPUnit_Framework_TestCase
{
    public function testReferenceIsReplacedByValueFromController()
    {
        $jarvis = new Jarvis();

        $jarvis['fake_controller'] = function () {
            return new FakeController();
        };

        $fakeController = $jarvis['fake_controller'];

        $callback = [new Reference('fake_controller'), 'randomAction'];

        $callback = $jarvis->callbackResolver->resolve($callback);

        $this->assertInstanceOf(\Closure::class, $callback);
    }

    public function testResolveAcceptAnyCallableCallback()
    {
        $jarvis = new Jarvis();

        try {
            $jarvis->callbackResolver->resolve([new FakeController(), 'randomAction']);
            $jarvis->callbackResolver->resolve(['DateTime', 'createFromFormat']);
            $jarvis->callbackResolver->resolve('rand');
            $jarvis->callbackResolver->resolve(function () {});
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

        $jarvis->callbackResolver->resolve([new FakeController(), 'unknownAction']);
    }
}
