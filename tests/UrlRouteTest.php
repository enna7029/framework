<?php

namespace Enna\Framework\tests;

use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Enna\Framework\Request;
use Enna\Framework\Route;

class UrlRouteTest extends TestCase
{
    use InteractsWithApp;

    /** @var Route|MockInterface */
    protected $route;

    protected function tearDown(): void
    {
        Mockery::close();
    }

    protected function setUp(): void
    {
        $this->prepareApp();

        $this->route = new Route($this->app);
    }

    public function testUrlDispatch()
    {
        $controller = Mockery::mock(FooClass::class);
        $controller->shouldReceive('index')->andReturn('bar');

        $this->app->shouldReceive('parseClass')->once()->with('controller', 'Foo')
            ->andReturn($controller->mockery_getName());
        $this->app->shouldReceive('make')->with($controller->mockery_getName(), [], true)->andReturn($controller);

        $request  = $this->makeRequest('foo');
        $response = $this->route->dispatch($request);
        $this->assertEquals('bar', $response->getContent());
    }

    /**
     * @param        $path
     * @param string $method
     * @param string $host
     * @return Mockery\LegacyMockInterface|Mockery\MockInterface|Request
     */
    protected function makeRequest($path, $method = 'GET', $host = 'localhost')
    {
        $request = Mockery::mock(Request::class)->makePartial();
        $request->shouldReceive('host')->andReturn($host);
        $request->shouldReceive('pathinfo')->andReturn($path);
        $request->shouldReceive('url')->andReturn('/' . $path);
        $request->shouldReceive('method')->andReturn(strtoupper($method));
        return $request;
    }

}
