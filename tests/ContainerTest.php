<?php

use Jarvis\Skill\DependencyInjection\Container;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class ContainerTest extends TestCase
{
    public function test_with_string()
    {
        $dic = new Container();
        $dic['string'] = 'string';

        $this->assertEquals('string', $dic['string']);
    }

    public function test_with_object()
    {
        $dic = new Container();
        $dic['object'] = new \stdClass();

        $this->assertInstanceOf('stdClass', $dic['object']);
    }

    public function test_with_closure()
    {
        $dic = new Container();
        $dic['closure'] = function () {
            return new \DateTime();
        };

        $this->assertInstanceOf('DateTime', $dic['closure']);
    }

    public function test_factory()
    {
        $dic = new Container();
        $dic->factory('factory', function () {
            return new \stdClass();
        });

        $this->assertInstanceOf('stdClass', $serviceOne = $dic['factory']);
        $this->assertInstanceOf('stdClass', $serviceTwo = $dic['factory']);

        $this->assertNotSame($serviceOne, $serviceTwo);
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage Service factory must be a closure or an invokable object.
     */
    public function test_factory_with_invalid_value()
    {
        $dic = new Container();

        $dic->factory('fail', true);
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage Service factory must be a closure or an invokable object.
     */
    public function test_factory_with_invalid_object()
    {
        $dic = new Container();

        $dic->factory('fail', new \stdClass());
    }

    /**
     * @expectedException          RuntimeException
     * @expectedExceptionMessage   Cannot override locked value "key"
     */
    public function test_lock()
    {
        $dic = new Container();
        $dic['key'] = 'value';
        $dic->lock('key');

        $dic['key'] = 'override';
    }

    /**
     * @expectedException          InvalidArgumentException
     * @expectedExceptionMessage   Identifier "key" is not defined.
     */
    public function test_lock_undefined_key()
    {
        $dic = new Container();
        $dic->lock('key');
    }

    /**
     * @expectedException          InvalidArgumentException
     * @expectedExceptionMessage   Identifier "key" is not defined.
     */
    public function test_get_undefined_key()
    {
        $dic = new Container();
        $dic['key'];
    }

    public function test_alias()
    {
        $dic = new Container();
        $dic['service'] = function () {
            $object = new \stdClass();
            $object->value = microtime();

            return $object;
        };

        $this->assertFalse(isset($id['service_alias']));
        $dic->alias('service_alias', 'service');

        $this->assertTrue(isset($dic['service_alias']));
        $this->assertInstanceOf('stdClass', $serviceOne = $dic['service']);
        $this->assertInstanceOf('stdClass', $serviceTwo = $dic['service_alias']);
        $this->assertEquals($serviceOne->value, $serviceTwo->value);
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage Cannot create alias for undefined value "service".
     */
    public function test_alias_of_undefined_value()
    {
        $dic = new Container();
        $dic->alias('service_alias', 'service');
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage Alias cannot be equals to value identifier.
     */
    public function test_alias_same_to_identifier()
    {
        $dic = new Container();
        $dic['parameter'] = 'value';

        $dic->alias('parameter', 'parameter');
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage Value's identifier cannot be equal to existing alias.
     */
    public function test_set_value_with_existing_alias_as_identifier()
    {
        $dic = new Container();
        $dic['service'] = function ($container) {
            return $container;
        };
        $dic->alias('service_alias', 'service');

        $dic['service_alias'] = 'value';
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage Alias cannot be equals to value identifier.
     */
    public function test_alias_same_to_already_existing_identifier()
    {
        $dic = new Container();
        $dic['parameter'] = 'value';
        $dic['foo'] = function () {
            return 'bar';
        };

        $dic->alias('foo', 'parameter');
    }

    public function test_container_passed_as_parameter()
    {
        $dic = new Container();
        $dic['service'] = function (Container $container) {
            return $container;
        };

        $dic->factory('factory', function (Container $container) {
            return $container;
        });

        $this->assertSame($dic, $dic['service']);
        $this->assertSame($dic, $dic['factory']);
    }

    public function test_unset()
    {
        $dic = new Container();
        $dic['key'] = 'value';
        $dic['service'] = function () {
            return new \stdClass();
        };
        $dic->lock('key');
        $dic->alias('key_alias', 'key');

        try {
            $dic['key'] = 'another value';
            $this->fail('Raises of RuntimeException expected.');
        } catch (\Exception $e) {
            $this->assertInstanceOf('RuntimeException', $e);
            $this->assertEquals('Cannot override locked value "key".', $e->getMessage());
        }

        unset($dic['key'], $dic['service']);
        $this->assertFalse(isset($dic['key']));
        $this->assertFalse(isset($dic['service']));
        $this->assertFalse(isset($dic['key_alias']));

        try {
            $dic['key'] = 'new value';
            $this->assertTrue(true);
        } catch (\RuntimeException $e) {
            $this->fail('`key` value must not be locked after unset() call.');
        }
    }

    public function test_unset_alias()
    {
        $dic = new Container();
        $dic['param'] = 'value';
        $dic->alias('param_alias', 'param');

        unset($dic['param_alias']);
        $this->assertTrue(isset($dic['param']));
        $this->assertFalse(isset($dic['param_alias']));
    }

    public function test_find()
    {
        $dic = new Container();

        $this->assertEquals([], $dic->find('random'));

        $callable = function () {
            return time();
        };

        $dic['jarvis'] = $callable;
        $this->assertEquals([$callable()], $dic->find('jarvis'));
        $this->assertEquals([$callable()], $dic->find('jarvis*'));

        $dic['jarvis_1'] = $callable;
        $this->assertCount(2, $dic->find('jarvis*'));

        $dic->alias('jarvis.extension', 'jarvis');
        $dic->alias('jarvis.plugin', 'jarvis');
        $dic->alias('jarvis.bundle', 'jarvis');

        $this->assertCount(3, $dic->find('jarvis.*'));

        $dic->alias('jarvis.demo.bundle.class', 'jarvis');
        $dic->alias('jarvis.comment.bundle.bundle', 'jarvis');
        $dic->alias('jarvis.common.bundle.source', 'jarvis');

        $this->assertCount(1, $dic->find('jarvis.*.bundle'));
    }

    public function test_container_implements_PSR11_ContainerInterface()
    {
        $dic = new Container();

        $this->assertInstanceOf(ContainerInterface::class, $dic);
    }

    public function test_get_has_same_behavior_than_offsetGet()
    {
        $dic = new Container();
        $dic['test'] = new \stdClass();

        $this->assertSame($dic->offsetGet('test'), $dic->get('test'));
    }

    /**
     * @expectedException \Psr\Container\NotFoundExceptionInterface
     */
    public function test_get_unknown_identifier()
    {
        $dic = new Container();

        $dic->get('random_id');
    }

    public function test_get_has_same_behavior_than_offsetExists()
    {
        $dic = new Container();

        $this->assertFalse($dic->offsetExists('foo'));
        $this->assertSame($dic->offsetExists('foo'), $dic->has('foo'));

        $dic['foo'] = 'bar';
        $this->assertTrue($dic->offsetExists('foo'));
        $this->assertSame($dic->offsetExists('foo'), $dic->has('foo'));
    }
}
