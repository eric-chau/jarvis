<?php

namespace Jarvis\Tests;

use Jarvis\Jarvis;
use Jarvis\Skill\EventBroadcaster\BroadcasterInterface;
use Jarvis\Skill\EventBroadcaster\RunEvent;
use Jarvis\Skill\EventBroadcaster\SimpleEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class JarvisTest extends TestCase
{
    public function test_debug_variable()
    {
        // By default Jarvis is started with debug setted to false.
        $app = new Jarvis();

        $this->assertFalse($app['debug']);

        // You can change the debug value by passing your debug value in settings array.
        $app = new Jarvis(['debug' => true]);

        $this->assertTrue($app['debug']);
    }

    public function test_custom_container_provider()
    {
        $app = new Jarvis(['providers' => []]);

        $this->assertFalse(isset($app['fake_container_provider_called']));

        $app = new Jarvis(['providers' => 'Jarvis\Tests\FakeContainerProvider']);

        $this->assertTrue(isset($app['fake_container_provider_called']));
        // ensure that Jarvis container provider is called first
        $this->assertTrue($app['is_request_already_defined']);
    }

    public function test_catch_all_exceptions_to_convert_them_to_response()
    {
        $app = new Jarvis(['debug' => true]);

        $app['router']
            ->beginRoute()
                ->setHandler(function () {
                    throw new \Exception('Hello, world!');
                })
            ->end()
        ;

        $this->assertInstanceOf(Response::class, $response = $app->run());
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(sprintf(
            '[Exception] error in %s at line 52 with message: %s',
            __FILE__,
            'Hello, world!'
        ), $response->getContent());

        // if debug = false, there is no error message in returned response
        $app = new Jarvis(['debug' => false]);

        $app['router']
            ->beginRoute()
                ->setHandler(function () {
                    throw new \Exception('Hello, world!');
                })
            ->end()
        ;

        $response = $app->run();
        $this->assertSame('', $response->getContent());
    }

    public function test_invalid_route_returns_response_status_code_404()
    {
        $app = new Jarvis();

        $this->assertSame(404, $app->run()->getStatusCode());
    }

    public function test_wrong_method_route_returns_response_status_code_405()
    {
        $app = new Jarvis();

        $app['router']
            ->beginRoute()
                ->setMethod('post')
                ->setHandler(function () {
                    return 'Hello, world!';
                })
            ->end()
        ;

        $this->assertSame(405, $app->run()->getStatusCode());
    }

    public function test_run_is_stopped_if_RunEvent_has_response()
    {
        $app = new Jarvis();

        $receiver = new FakeReceiver();

        $app->on(BroadcasterInterface::RUN_EVENT, [$receiver, 'onRunEventSetResponse']);
        $app->on(BroadcasterInterface::CONTROLLER_EVENT, [$receiver, 'onControllerEvent']);
        $app->on(BroadcasterInterface::RESPONSE_EVENT, [$receiver, 'onResponseEvent']);

        $this->assertNull($receiver->runEvent);
        $this->assertNull($receiver->controllerEvent);
        $this->assertNull($receiver->responseEvent);

        $response = $app->run();

        $this->assertSame(Response::HTTP_NOT_MODIFIED, $response->getStatusCode());
        $this->assertInstanceOf(RunEvent::class, $receiver->runEvent);
        $this->assertNull($receiver->controllerEvent);
        $this->assertNull($receiver->responseEvent);
    }

    public function test_modify_response_on_ResponseEvent_is_persistent()
    {
        $app = new Jarvis();

        $receiver = new FakeReceiver();
        $controller = function () {
            return new Response('foo');
        };

        $app->on(BroadcasterInterface::RESPONSE_EVENT, [$receiver, 'modifyResponseOnResponseEvent']);

        $app['router']
            ->beginRoute()
                ->setHandler($controller)
            ->end()
        ;

        $this->assertNull($receiver->responseEvent);

        $response = $app->run();

        $this->assertNotNull($receiver->responseEvent);
        $this->assertNotSame($controller()->getContent(), $response->getContent());
        $this->assertSame('bar', $response->getContent());

    }

    public function test_access_to_locked_value_as_Jarvis_attribute()
    {
        $app = new Jarvis();

        $app['foo'] = 'bar';
        $app->lock('foo');

        $this->assertSame('bar', $app->foo);
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage "foo" is not a key of a locked value.
     */
    public function test_access_to_not_locked_value_or_non_existent_value_as_Jarvis_attribute_raise_exception()
    {
        $app = new Jarvis();

        $app['foo'] = 'bar';
        $app->foo;
    }

    /**
     * @expectedException        \LogicException
     * @expectedExceptionMessage You are not allowed to set new attribute into Jarvis.
     */
    public function test_set_new_attribute_to_Jarvis_raise_exception()
    {
        $app = new Jarvis();

        $app->foo = 'bar';
    }

    public function test_run_convert_string_returned_by_callback_into_SymfonyResponse()
    {
        $app = new Jarvis();

        $str = 'hello world';

        $app['router']
            ->beginRoute()
                ->setHandler(function () use ($str) {
                    return $str;
                })
            ->end()
        ;

        $this->assertInstanceOf(Response::class, $response = $app->run());
        $this->assertSame($str, $response->getContent());
    }

    public function test_broadcast_TerminateEvent_on_destruct()
    {
        $app = new Jarvis();

        $receiver = new FakeReceiver();
        $app->on(BroadcasterInterface::TERMINATE_EVENT, [$receiver, 'onEventBroadcast']);

        $this->assertNull($receiver->event);

        unset($app);
        gc_collect_cycles();

        $this->assertNotNull($receiver->event);
        $this->assertInstanceOf(SimpleEvent::class, $receiver->event);
    }

    public function test_request_service()
    {
        $app = new Jarvis();

        $this->assertTrue(isset($app['request']));
        $this->assertInstanceOf(Request::class, $app['request']);
    }

    public function test_settings_service()
    {
        $app = new Jarvis([
            'extra' => [
                'foo' => 'bar',
            ],
        ]);

        $this->assertTrue(isset($app['foo.settings']));
        $this->assertSame('bar', $app['foo.settings']);
    }

    public function test_session_service()
    {
        $app = new Jarvis();

        $this->assertSame($app['request']->getSession(), $app['session']);
    }

    public function test_app_service()
    {
        $app = new Jarvis();

        $this->assertSame($app, $app['app']);
    }

    public function test_adding_closure_with_return_type_generate_alias()
    {
        $app = new Jarvis();

        $this->assertFalse(isset($app['DateTime']));

        $app['foobar'] = function (): \DateTime {
            return new \DateTime();
        };

        $this->assertTrue(isset($app['DateTime']));
        $this->assertSame($app['foobar'], $app['DateTime']);

        // You can use the returned type as identifier
        $app['stdClass'] = function(): \stdClass {
            return new \stdClass();
        };

        $this->assertTrue(isset($app['stdClass']));

        // If two closures have the same return type, the returned type alias is
        // deleted to preserve unicity and consistence
        $app['array_object_1'] = function (): \ArrayObject {
            return new \ArrayObject();
        };

        $this->assertTrue(isset($app['ArrayObject']));

        $app['array_object_2'] = function (): \ArrayObject {
            return new \ArrayObject();
        };

        $this->assertFalse(isset($app['ArrayObject']));

        // Only returned type of class or interface work
        $app['hello_world'] = function (): string {
            return 'Hello, world!';
        };

        $this->assertFalse(isset($app['string']));
    }
}
