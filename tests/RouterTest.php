<?php

namespace Jarvis\Tests;

use Jarvis\Jarvis;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class RouterTest extends \PHPUnit_Framework_TestCase
{
    public function testRouterNotCaseSensibleToHttpMethod()
    {
        $jarvis = new Jarvis();

        $jarvis['router']
            ->beginRoute()
                ->setHandler(function() {
                    return 'Hello, world!';
                })
            ->end()
        ;

        $response = $jarvis->run();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Hello, world!', $response->getContent());
    }

    public function testBeginRoute()
    {
        $jarvis = new Jarvis();

        $response = $jarvis->run(Request::create('/hello/jarvis'));

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());

        $jarvis['router']
            ->beginRoute()
                ->setPattern('/hello/{name}')
                ->setHandler(function($name) {
                    return "Hello $name!";
                })
            ->end()
            ->beginRoute('with_params')
                ->setPattern('/hello/{name:\w+}/{id:\d+}')
                ->setHandler(function($name, $id) {
                    return "$name ($id)";
                })
            ->end()
        ;

        $response = $jarvis->run(Request::create('/hello/jarvis'));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('Hello jarvis!', $response->getContent());

        $response = $jarvis->run(Request::create('/hello/jarvis/123'));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('jarvis (123)', $response->getContent());
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Cannot generate URI for 'foobar_route' cause it does not exist.
     */
    public function testGetUriOfInvalidRouteName()
    {
        $jarvis = new Jarvis();

        $jarvis['router']->uri('foobar_route');
    }

    public function testGetUri()
    {
        $jarvis = new Jarvis();

        $jarvis['router']
            ->beginRoute('default')
                ->setHandler(function() {
                    return 'default';
                })
            ->end()
            ->beginRoute('with_params')
                ->setPattern('/hello/{name:\w+}/{id:\d+}')
                ->setHandler(function($name, $id) {
                    return "$name ($id)";
                })
            ->end()
        ;

        $this->assertSame('/', $jarvis['router']->uri('default'));
        $this->assertSame('/hello/jarvis/123', $jarvis['router']->uri('with_params', [
            'name' => 'jarvis',
            'id'   => 123,
        ]));
        $this->assertSame('/hello/{name:\w+}/123', $jarvis['router']->uri('with_params', [
            'id'   => 123,
        ]));
    }

    public function testHostAndSchemeGetterAndSetter()
    {
        $jarvis = new Jarvis();

        $this->assertSame('', $jarvis->router->host());
        $this->assertSame('http', $jarvis->router->scheme());

        $host = 'hostname.com:8000';
        $jarvis->router->setHost($host);
        $this->assertSame($host, $jarvis->router->host());
        $jarvis->router->setHost(null);
        $this->assertSame('', $jarvis->router->host());


        $jarvis->router->setScheme(null);
        $this->assertSame('http', $jarvis->router->scheme());
        $jarvis->router->setScheme('');
        $this->assertSame('http', $jarvis->router->scheme());
        $jarvis->router->setScheme('https');
        $this->assertSame('https', $jarvis->router->scheme());
    }

    public function testGetUrl()
    {
        $jarvis = new Jarvis();

        $this->assertSame('/foo/bar', $jarvis->router->url('/foo/bar'));

        $jarvis->router->setScheme('https');
        $jarvis->router->setHost('');
        $this->assertSame('/foo/bar', $jarvis->router->url('/foo/bar'));

        $jarvis->router->setHost('github.com/');
        $this->assertSame('https://github.com/eric-chau/jarvis', $jarvis->router->url('///eric-chau/jarvis'));
    }
}
