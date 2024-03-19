<?php

namespace Enna\Framework\tests;

use Enna\Framework\Middleware;
use PHPUnit\Framework\TestCase;
use Mockery;
use Enna\Framework\Response;
use Enna\Framework\Request;
use Enna\Framework\Exception;
use Enna\Framework\Exception\Handle;
use Enna\Framework\Pipeline;

class MiddlewareTest extends TestCase
{
    use InteractsWithApp;

    /**
     * @var Middleware
     */
    protected $middleware;

    protected function tearDown(): void
    {
        Mockery::close();
    }

    protected function setUp(): void
    {
        $this->prepareApp();

        $this->middleware = new Middleware($this->app);
    }

    public function testSetMiddleware()
    {
        $this->middleware->add('BarMiddleware', 'bar');
        $this->assertEquals(1, count($this->middleware->all('bar')));

        $this->middleware->controller('BarMiddleware');
        $this->assertEquals(1, count($this->middleware->all('controller')));

        $this->middleware->import(['FooMiddleware']);
        $this->assertEquals(1, count($this->middleware->all()));

        $this->middleware->unshift(['BazMiddleware', 'baz']);
        $this->assertEquals(2, count($this->middleware->all()));

        $this->assertEquals([['BazMiddleware', 'handle'], 'baz'], $this->middleware->all()[0]);

        $this->config->shouldReceive('get')->with('middleware.alias', [])
            ->andReturn(['foo' => ['FooMiddleware', 'FarMiddleware']]);

        $this->middleware->add('foo');
        $this->assertEquals(3, count($this->middleware->all()));

        $this->middleware->add(function () {
        });
        $this->middleware->add(function () {
        });
        $this->assertEquals(5, count($this->middleware->all()));
    }

    public function testPipelineAndEnd()
    {
        $bar = Mockery::mock("overload:BarMiddleware");
        $foo = Mockery::mock("overload:FooMiddleware", Foo::class);

        $request  = Mockery::mock(Request::class);
        $response = Mockery::mock(Response::class);

        $e = new Exception();

        $handle = Mockery::mock(Handle::class);
        $handle->shouldReceive('report')->with($e)->andReturnNull();
        $handle->shouldReceive('render')->with($request, $e)->andReturn($response);

        $foo->shouldReceive('handle')->once()->andReturnUsing(function ($request, $next) {
            return $next($request);
        });
        $bar->shouldReceive('handle')->once()->andReturnUsing(function ($request, $next) use ($e) {
            $next($request);
            throw  $e;
        });

        $foo->shouldReceive('end')->once()->with($response)->andReturnNull();

        $this->app->shouldReceive('make')->with(Handle::class)->andReturn($handle);

        $this->config->shouldReceive('get')->once()->with('middleware.priority', [])
            ->andReturn(['FooMiddleware', 'BarMiddleware']);

        $this->middleware->import([function ($request, $next) {
            return $next($request);
        }, 'BarMiddleware', 'FooMiddleware']);

        $this->assertInstanceOf(Pipeline::class, $pipeline = $this->middleware->pipeline());

        $pipeline->send($request)->then(function ($request) use ($e, $response) {
            throw $e;
        });

        $this->middleware->end($response);
    }
}

class Foo
{
    public function end(Response $response)
    {
    }
}