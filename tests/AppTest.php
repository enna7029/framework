<?php

namespace Enna\Framework\tests;

use Enna\Framework\Exception\ClassNotFoundException;
use PHPUnit\Framework\TestCase;
use Enna\Framework\App;
use Enna\Framework\Service;
use Enna\Framework\Env;
use Enna\Framework\Event;
use Mockery;
use stdClass;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Enna\Framework\Event\AppInit;

class SomeService extends Service
{
    public $bind = [
        'some' => 'class',
    ];

    public function register()
    {

    }

    public function boot()
    {

    }
}

class AppTest extends TestCase
{
    protected $app;

    protected function setUp(): void
    {
        $this->app = new App();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testService()
    {
        $this->app->register(stdClass::class);
        $this->assertInstanceOf(stdClass::class, $this->app->getService(stdClass::class));

        $service = Mockery::mock(SomeService::class);
        $service->shouldReceive('register')->once();
        $this->app->register($service);
        $this->assertEquals($service, $this->app->getService(SomeService::class));

        $service2 = Mockery::mock(SomeService::class);
        $service2->shouldReceive('register')->once();
        $this->app->register($service2, true);
        $this->assertEquals($service2, $this->app->getService(SomeService::class));

        $service->shouldReceive('boot')->once();
        $service2->shouldReceive('boot')->once();
        $this->app->boot();
    }

    public function testDebug()
    {
        $this->app->debug(true);
        $this->assertTrue($this->app->isDebug());

        $this->app->debug(false);
        $this->assertFalse($this->app->isDebug());
    }

    public function testNamespace()
    {
        $namespace = 'test';

        $this->app->setNamespace($namespace);
        $this->assertEquals($namespace, $this->app->getNamespace());
    }

    public function testVersion()
    {
        $this->assertEquals(App::VERSION, $this->app->version());
    }

    public function testPath()
    {
        $rootPath = __DIR__ . DIRECTORY_SEPARATOR;

        $app = new App($rootPath);

        $this->assertEquals($rootPath, $app->getRootPath());
        $this->assertEquals(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR, $app->getCorePath());
        $this->assertEquals($rootPath . 'app' . DIRECTORY_SEPARATOR, $app->getAppPath());
        $this->assertEquals($rootPath . 'config' . DIRECTORY_SEPARATOR, $app->getConfigPath());
        $this->assertEquals($rootPath . 'runtime' . DIRECTORY_SEPARATOR, $app->getRuntimePath());
    }

    public function testInitialize()
    {
        $root = vfsStream::setup('rootDir', null, [
            '.env' => '',
            'app' => [
                'common.php' => '',
                'event.php' => '<?php return ["bind"=>[],"listen"=>[],"subscribe"=>[]];',
                'provider.php' => '<?php return [];',
            ],
            'config' => [
                'app.php' => '<?php return [];',
            ],
        ]);

        $app = $this->prepareAppForInitialize($root);

        $app->debug(false);
        $app->initialize();

        $this->assertIsInt($app->getBeginMem());
        $this->assertIsFloat($app->getBeginTime());

        $this->assertTrue($app->initialized());
    }

    protected function prepareAppForInitialize(vfsStreamDirectory $root)
    {
        $rootPath = $root->url() . DIRECTORY_SEPARATOR;

        $app = new App($rootPath);
        $initializer = Mockery::mock();
        $initializer->shouldReceive('init')->once()->with($app);
        (function () use ($initializer) {
            $this->initializers = [$initializer->mockery_getName()];
        })->call($app);

        $env = Mockery::mock(Env::class);
        $env->shouldReceive('load')->once()->with($rootPath . '.env');
        $env->shouldReceive('get')->once()->with('config_ext', '.php')->andReturn('.php');
        $env->shouldReceive('get')->once()->with('app_debug')->andReturn(false);

        $event = Mockery::mock(Event::class);
        $event->shouldReceive('trigger')->once()->with(AppInit::class);
        $event->shouldReceive('bind')->once()->with([]);
        $event->shouldReceive('listenEvents')->once()->with([]);
        $event->shouldReceive('subscribe')->once()->with([]);

        $app->instance($initializer->mockery_getName(), $initializer);
        $app->instance('env', $env);
        $app->instance('event', $event);

        return $app;
    }

    public function testFactory()
    {
        $this->assertInstanceOf(stdClass::class, App::factory(stdClass::class));

        $this->expectException(ClassNotFoundException::class);

        App::factory('SomeClass');
    }

    public function testParseClass()
    {
        $this->assertEquals('app\\controller\\SomeClass', $this->app->parseClass('controller', 'SomeClass'));

        $this->app->setNamespace('app2');
        $this->assertEquals('app2\\controller\SomeClass', $this->app->parseClass('controller', 'SomeClass'));
    }
}