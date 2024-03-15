<?php

namespace Enna\Framework\tests;

use Enna\Framework\Container;
use PHPUnit\Framework\TestCase;
use Enna\Framework\Exception\ClassNotFoundException;
use Enna\Framework\Exception\FuncNotFoundException;

class Enna
{
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function some(Container $container)
    {
    }

    protected function protectionFun()
    {
        return true;
    }

    public static function test(Container $container)
    {
        return $container;
    }

    public static function __make()
    {
        return new self('Enna');
    }
}

class SomeClass
{
    public $container;

    public $count = 0;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }
}

class ContainerTest extends TestCase
{
    protected function tearDown(): void
    {
        Container::setInstance(null);
    }

    public function testClosureResolution()
    {
        $container = new Container();

        Container::setInstance($container);

        $container->bind('name', function () {
            return 'Enna';
        });

        $this->assertEquals('Enna', $container->make('name'));
        $this->assertEquals('Enna', $container->pull('name'));
    }

    public function testGet()
    {
        $container = new Container;

        $container->bind('name', function () {
            return 'Enna';
        });

        $this->assertSame('Enna', $container->get('name'));
    }

    public function testExist()
    {
        $container = new Container();

        $container->bind('name', function () {
            return 'Enna';
        });

        $this->assertFalse($container->exists('name'));

        $container->make('name');

        $this->assertTrue($container->exists('name'));
    }

    public function testInstance()
    {
        $container = new Container();

        $container->bind('name', function () {
            return 'Enna';
        });

        $this->assertEquals('Enna', $container->get('name'));

        $container->bind('name2', Enna::class);

        $this->assertFalse($container->exists('name2'));

        $object = new \stdClass();

        $container->instance('name2', $object);

        $this->assertTrue($container->exists('name2'));

        $this->assertTrue($container->exists(Enna::class));

        $this->assertEquals($object, $container->make(Enna::class));

        $container->delete('name2');

        $this->assertFalse($container->exists('name2'));

        unset($container->name1);

        $this->assertFalse($container->exists('name1'));
    }

    public function testBind()
    {
        $container = new Container();

        $object = new \stdClass();

        $container->bind(['name' => Enna::class]);

        $container->bind('name2', $object);

        $container->bind('name3', Enna::class);

        $container->name4 = $object;

        $container['name5'] = $object;

        $this->assertTrue(isset($container->name4));

        $this->assertTrue(isset($container['name5']));

        $this->assertInstanceOf(Enna::class, $container->get('name'));

        $this->assertEquals($object, $container->get('name2'));

        $this->assertEquals($object, $container->name4);

        $this->assertEquals($object, $container['name5']);

        $this->assertInstanceOf(Enna::class, $container->get('name3'));

        unset($container->name);

        $this->assertFalse(isset($container->name));

        unset($container->name3);

        $this->assertFalse(isset($container->name3));
    }

    public function testAutoConcreteResolution()
    {
        $container = new Container();

        $enna = $container->make(Enna::class);

        $this->assertInstanceOf(Enna::class, $enna);

        $this->assertSame('Enna', $enna->name);
    }

    public function testGetAndSetInstance()
    {
        $this->assertInstanceOf(Container::class, Container::getInstance());

        $object = new \stdClass();

        Container::setInstance($object);

        $this->assertSame($object, Container::getInstance());

        Container::setInstance(function () {
            return $this;
        });

        $this->assertSame($this, Container::getInstance());
    }

    public function testResolving()
    {
        $container = new Container();
        $container->bind(Container::class, $container);

        $container->resolving(function (SomeClass $someClass, Container $container) {
            $someClass->count++;
        });

        $container->resolving(SomeClass::class, function (SomeClass $someClass, Container $container) {
            $someClass->count++;
        });

        $someClass = $container->invokeClass(SomeClass::class);

        $this->assertEquals(2, $someClass->count);
    }

    public function testInvokeProtectionMethod()
    {
        $container = new Container();

        $this->assertTrue($container->invokeMethod([Enna::class, 'protectionFun'], [], true));
    }

    public function testInvoke()
    {
        $container = new Container();

        Container::setInstance($container);

        $container->bind(Container::class, $container);

        $stub = $this->createMock(Enna::class);

        $stub->expects($this->once())->method('some')->with($container)->will($this->returnSelf());

        $container->invokeMethod([$stub, 'some']);

        $this->assertEquals('48', $container->invoke('ord', ['0']));

        $this->assertSame($container, $container->invoke(Enna::class . '::test', []));

        $this->assertSame($container, $container->invokeMethod(Enna::class . '::test'));

        $reflect = new \ReflectionMethod($container, 'exists');

        $this->assertTrue($container->invokeReflectMethod($container, $reflect, [Container::class]));

        $this->assertSame($container, $container->invoke(function (Container $container) {
            return $container;
        }));

        $this->assertSame($container, $container->invoke(Enna::class . '::test'));

        $object = $container->invokeClass(SomeClass::class);
        $this->assertInstanceOf(SomeClass::class, $object);
        $this->assertSame($container, $object->container);

        $stdClass = new \stdClass();

        $container->invoke(function (Container $container, \stdClass $stdObject, $key1, $lowKey, $key2 = 'default') use ($stdClass) {
            $this->assertEquals('value1', $key1);
            $this->assertEquals('default', $key2);
            $this->assertEquals('value2', $lowKey);
            $this->assertSame($stdClass, $stdObject);
            return $container;
        }, ['some' => $stdClass, 'key1' => 'value1', 'low_key' => 'value2']);
    }

    public function testInvokeMethodNotExists()
    {
        $container = $this->resolveContainer();
        $this->expectException(FuncNotFoundException::class);

        $container->invokeMethod([SomeClass::class, 'any']);
    }

    protected function resolveContainer()
    {
        $container = new Container();

        Container::setInstance($container);
        return $container;
    }
}