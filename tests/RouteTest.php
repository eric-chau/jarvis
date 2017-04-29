<?php

namespace Jarvis\Tests;

use Jarvis\Jarvis;
use Jarvis\Skill\Routing\Route;
use PHPUnit\Framework\TestCase;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class RouteTest extends TestCase
{
    public function test_setMethod()
    {
        $app = new Jarvis();

        $route = new Route($app['router']);
        $this->assertSame(['get'], $route->method());

        $route->setMethod('put');
        $this->assertSame(['put'], $route->method());

        $route->setMethod(['patch', 'post']);
        $this->assertSame(['patch', 'post'], $route->method());
    }
}
