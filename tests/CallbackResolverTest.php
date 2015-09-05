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

        $callback = $jarvis->callback_resolver->resolve($callback);

        $this->assertNotInstanceOf(Reference::class, $callback[0]);
        $this->assertSame($fakeController, $callback[0]);
    }

    public function testResolveAcceptAnyCallableCallback()
    {
        $jarvis = new Jarvis();

        $jarvis->callback_resolver->resolve([new FakeController(), 'randomAction']);
        $jarvis->callback_resolver->resolve(['DateTime', 'createFromFormat']);
        $jarvis->callback_resolver->resolve('rand');
        $jarvis->callback_resolver->resolve(function () {});

        $this->assertTrue(true);
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Provided callback is not callable.
     */
    public function testResolveRaisesExceptionOnInvalidCallback()
    {
        $jarvis = new Jarvis();

        $jarvis->callback_resolver->resolve([new FakeController(), 'unknownAction']);
    }
}
