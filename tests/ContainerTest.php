<?php

use Jarvis\Skill\DependencyInjection\Container;

/**
 * @author Eric Chau <eriic.chau@gmail.com>
 */
class ContainerTest extends \PHPUnit_Framework_TestCase
{
    public function testWithString()
    {
        $dic = new Container();
        $dic['string'] = 'string';

        $this->assertEquals('string', $dic['string']);
    }

    public function testWithObject()
    {
        $dic = new Container();
        $dic['object'] = new \stdClass();

        $this->assertInstanceOf('stdClass', $dic['object']);
    }

    public function testWithClosure()
    {
        $dic = new Container();
        $dic['closure'] = function () {
            return new \DateTime();
        };

        $this->assertInstanceOf('DateTime', $dic['closure']);
    }

    public function testFactory()
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
     * @expectedExceptionMessage Service factory must be a Closure or an invokable object.
     */
    public function testSetFactoryWithInvalidValue()
    {
        $dic = new Container();

        $dic->factory('fail', true);
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage Service factory must be a Closure or an invokable object.
     */
    public function testSetFactoryWithInvalidObject()
    {
        $dic = new Container();

        $dic->factory('fail', new \stdClass());
    }

    /**
     * @expectedException          RuntimeException
     * @expectedExceptionMessage   Cannot override locked value `key`
     */
    public function testLock()
    {
        $dic = new Container();
        $dic['key'] = 'value';
        $dic->lock('key');

        $dic['key'] = 'override';
    }

    /**
     * @expectedException          InvalidArgumentException
     * @expectedExceptionMessage   Identifier `key` is not defined.
     */
    public function testLockInvalidKey()
    {
        $dic = new Container();
        $dic->lock('key');
    }

    /**
     * @expectedException          InvalidArgumentException
     * @expectedExceptionMessage   Identifier `key` is not defined.
     */
    public function testGetInvalidKey()
    {
        $dic = new Container();
        $dic['key'];
    }

    public function testAlias()
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
     * @expectedExceptionMessage Cannot create alias for undefined value `service`.
     */
    public function testSetAliasToUndefinedValue()
    {
        $dic = new Container();
        $dic->alias('service_alias', 'service');
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage Alias cannot be equals to value identifier.
     */
    public function testSetAliasEqualsToIdentifier()
    {
        $dic = new Container();
        $dic['parameter'] = 'value';

        $dic->alias('parameter', 'parameter');
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage Value's identifier cannot be equal to existing alias.
     */
    public function testSetValueWithIdOfExistingAlias()
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
    public function testSetAliasEqualsToAnotherValueIdentifier()
    {
        $dic = new Container();
        $dic['parameter'] = 'value';
        $dic['foo'] = function () {
            return 'bar';
        };

        $dic->alias('foo', 'parameter');
    }

    public function testPassContainerAsParameter()
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

    public function testUnset()
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
            $this->assertEquals('Cannot override locked value `key`.', $e->getMessage());
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

    public function testUnsetAlias()
    {
        $dic = new Container();
        $dic['param'] = 'value';
        $dic->alias('param_alias', 'param');

        unset($dic['param_alias']);
        $this->assertTrue(isset($dic['param']));
        $this->assertFalse(isset($dic['param_alias']));
    }

    public function testFind()
    {
        $dic = new Container();

        $this->assertEquals([], $dic->find('random'));

        $callable = function () {
            return time();
        };

        $dic['urf'] = $callable;
        $this->assertEquals([$callable()], $dic->find('urf'));
        $this->assertEquals([$callable()], $dic->find('urf*'));

        $dic['urf_1'] = $callable;
        $this->assertCount(2, $dic->find('urf*'));

        $dic
            ->alias('urf.extension', 'urf')
            ->alias('urf.plugin', 'urf')
            ->alias('urf.bundle', 'urf')
        ;

        $this->assertCount(3, $dic->find('urf.*'));

        $dic
            ->alias('urf.demo.bundle.class', 'urf')
            ->alias('urf.comment.bundle.bundle', 'urf')
            ->alias('urf.common.bundle.source', 'urf')
        ;
        $this->assertCount(1, $dic->find('urf.*.bundle'));
    }
}
