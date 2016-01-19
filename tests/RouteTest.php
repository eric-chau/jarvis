<?php

namespace Jarvis\Tests;

use Jarvis\Jarvis;
use Jarvis\Skill\Routing\Route;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class RouteTest extends \PHPUnit_Framework_TestCase
{
    public function testHttpMethodSetter()
    {
        $jarvis = new Jarvis();

        $route = new Route('test', $jarvis->router);

        $route->setMethod('put');
        $this->assertSame(['put'], $route->method());

        $route->setMethod(['patch', 'post']);
        $this->assertSame(['patch', 'post'], $route->method());
    }
}
