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
    public function test_Router_not_case_sensible_for_http_method()
    {
        $app = new Jarvis();

        $app['router']
            ->beginRoute()
                ->setHandler(function () {
                    return 'Hello, world!';
                })
            ->end()
        ;

        $response = $app->run();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Hello, world!', $response->getContent());
    }

    public function test_beginRoute()
    {
        $app = new Jarvis();

        $response = $app->run(Request::create('/hello/jarvis'));

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());

        $app['router']
            ->beginRoute()
                ->setPattern('/hello/{name}')
                ->setHandler(function ($name) {
                    return "Hello $name!";
                })
            ->end()
            ->beginRoute('with_params')
                ->setPattern('/hello/{name:\w+}/{id:\d+}')
                ->setHandler(function ($name, $id) {
                    return "$name ($id)";
                })
            ->end()
        ;

        $response = $app->run(Request::create('/hello/jarvis'));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('Hello jarvis!', $response->getContent());

        $response = $app->run(Request::create('/hello/jarvis/123'));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('jarvis (123)', $response->getContent());
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Cannot generate URI for 'foobar_route' cause it does not exist.
     */
    public function test_uri_of_invalid_route_name()
    {
        $app = new Jarvis();

        $app['router']->uri('foobar_route');
    }

    public function test_uri()
    {
        $app = new Jarvis();

        $app['router']
            ->beginRoute('default')
                ->setHandler(function () {
                    return 'default';
                })
            ->end()
            ->beginRoute('with_params')
                ->setPattern('/hello/{name:\w+}/{id:\d+}')
                ->setHandler(function ($name, $id) {
                    return "$name ($id)";
                })
            ->end()
        ;

        $this->assertSame('/', $app['router']->uri('default'));
        $this->assertSame('/hello/jarvis/123', $app['router']->uri('with_params', [
            'name' => 'jarvis',
            'id'   => 123,
        ]));
        $this->assertSame('/hello/{name:\w+}/123', $app['router']->uri('with_params', [
            'id'   => 123,
        ]));
    }

    public function test_getter_and_setter_for_host_and_scheme()
    {
        $app = new Jarvis();

        $this->assertSame('', $app['router']->host());
        $this->assertSame('http', $app['router']->scheme());

        $host = 'hostname.com:8000';
        $app['router']->setHost($host);
        $this->assertSame($host, $app['router']->host());
        $app['router']->setHost(null);
        $this->assertSame('', $app['router']->host());


        $app['router']->setScheme(null);
        $this->assertSame('http', $app['router']->scheme());
        $app['router']->setScheme('');
        $this->assertSame('http', $app['router']->scheme());
        $app['router']->setScheme('https');
        $this->assertSame('https', $app['router']->scheme());
    }

    public function test_url()
    {
        $app = new Jarvis();

        $this->assertSame('/foo/bar', $app['router']->url('/foo/bar'));

        $app['router']->setScheme('https');
        $app['router']->setHost('');
        $this->assertSame('/foo/bar', $app['router']->url('/foo/bar'));

        $app['router']->setHost('github.com/');
        $this->assertSame('https://github.com/eric-chau/jarvis', $app['router']->url('///eric-chau/jarvis'));
    }

    public function test_guessHost()
    {
        $app = new Jarvis();

        $this->assertEquals('', $app['router']->host());
        $this->assertEquals('http', $app['router']->scheme());

        $app['router']->guessHost(Request::create('/'));
        $this->assertEquals('localhost', $app['router']->host());
        $this->assertEquals('http', $app['router']->scheme());
        $this->assertEquals('http://localhost/hello', $app['router']->url('/hello'));

        $app['router']->guessHost(Request::create('/', 'GET', [], [], [], ['HTTP_HOST' => 'localhost:8000']));
        $this->assertEquals('http://localhost:8000/hello', $app['router']->url('/hello'));
    }
}
