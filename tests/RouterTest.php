<?php

namespace Jarvis\Tests;

use Jarvis\Jarvis;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class RouterTest extends \PHPUnit_Framework_TestCase
{
    public function testRouterNotCaseSensibleToHttpMethod()
    {
        $jarvis = new Jarvis();

        $jarvis['router']->addRoute('get', '/', [new FakeController(), 'randomAction']);
        $response = $jarvis->analyze();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(FakeController::class, $response->getContent());
    }
}
