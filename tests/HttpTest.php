<?php

namespace Enna\Framework\tests;

use Enna\Framework\App;
use Enna\Framework\Console;
use Enna\Framework\Event;
use Enna\Framework\Exception;
use Enna\Framework\Http;
use Enna\Framework\Log;
use Enna\Framework\Request;
use Enna\Framework\Response;
use Enna\Framework\Route;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Mockery;

class HttpTest extends TestCase
{
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|App
     */
    protected $app;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Http
     */
    protected $http;

    protected function tearDown(): void
    {
        Mockery::close();
    }

    protected function setUp(): void
    {
        $this->app = Mockery::mock(App::class)->makePartial();

        $this->http = Mockery::mock(Http::class, [$this->app])->shouldAllowMockingProtectedMethods()->makePartial();
    }

    public function testRun()
    {
        $root = vfsStream::setup('rootDir', null, [
            'app' => [
                'controller' => [],
                'middleware.php' => '<?php return [];',
            ],
            'route' => [
                'route.php' => '<?php return [];',
            ],
        ]);

        $this->app->shouldReceive('getBasePath')->andReturn($root->getChild('app')->url() . DIRECTORY_SEPARATOR);
        $this->app->shouldReceive('getRootPath')->andReturn($root->url() . DIRECTORY_SEPARATOR);

        $request = Mockery::mock(Request::class)->makePartial();
        $response = Mockery::mock(Response::class)->makePartial();

        $this->prepareApp($request, $response);

        $this->assertEquals($response, $this->http->run($request));
    }

    protected function prepareApp($request, $response)
    {
        $this->app->shouldReceive('initialized')->once()->andReturnFalse();
        $this->app->shouldReceive('initialize')->once();
        $this->app->shouldReceive('get')->with('request')->andReturn($request);
        $this->app->shouldReceive('instance')->once()->with('request', $request);

        $route = Mockery::mock(Route::class);

        $route->shouldReceive('dispatch')->withArgs(function ($req, $withRoute) use ($request) {
            if ($withRoute) {
                $withRoute();
            }
            return $req === $request;
        })->andReturn($response);

        $route->shouldReceive('config')->with('route_annotation')->andReturn(true);

        $this->app->shouldReceive('get')->with('route')->andReturn($route);

        $console = Mockery::mock(Console::class);

        $console->shouldReceive('call');

        $this->app->shouldReceive('get')->with('console')->andReturn($console);
    }

    public function testRunWithException()
    {
        $request = Mockery::mock(Request::class);
        $response = Mockery::mock(Response::class);

        $this->app->shouldReceive('instance')->once()->with('request', $request);
        $this->app->shouldReceive('initialize')->once();

        $exception = new Exception();

        $this->http->shouldReceive('runWithRequest')->once()->with($request)->andThrow($exception);

        $handle = Mockery::mock(Exception\Handle::class);

        $handle->shouldReceive('report')->once()->with($exception);
        $handle->shouldReceive('render')->once()->with($request, $exception)->andReturn($response);

        $this->app->shouldReceive('make')->with(Exception\Handle::class)->andReturn($handle);

        $this->assertEquals($response, $this->http->run($request));
    }

    public function testEnd()
    {
        $response = Mockery::mock(Response::class);
        $event = Mockery::mock(Event::class);

        $event->shouldReceive('trigger')->once()->with(Event\HttpEnd::class, $response);
        $this->app->shouldReceive('get')->once()->with('event')->andReturn($event);

        $log = Mockery::mock(Enna\Framework\Log::class);
        $log->shouldReceive('save')->once();
        $this->app->shouldReceive('get')->once()->with('log')->andReturn($log);

        $this->http->end($response);

        $this->assertTrue(true);
    }
}