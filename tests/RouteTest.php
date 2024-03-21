<?php

namespace Enna\Framework\tests;

use Enna\Framework\Helper\Str;
use Enna\Framework\Request;
use Enna\Framework\Response\Redirect;
use Enna\Framework\Route;
use PHPUnit\Framework\TestCase;
use Mockery;

class RouteTest extends TestCase
{
    use InteractsWithApp;

    /**
     * @var Route|Mockery\MockInterface
     */
    protected $route;

    protected function setUp(): void
    {
        $this->prepareApp();

        $this->config->shouldReceive('get')->with('route')->andReturn(['url_route_must' => true]);
        $this->route = new Route($this->app);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testSimpleRequest()
    {
        $this->route->get('foo', function () {
            return 'get-foo';
        });

        $this->route->put('foo', function () {
            return 'put-foo';
        });

        $this->route->group(function () {
            $this->route->post('foo', function () {
                return 'post-foo';
            });
        });

        $request = $this->makeRequest('foo', 'post');
        $response = $this->route->dispatch($request);
        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('post-foo', $response->getContent());

        $request = $this->makeRequest('foo', 'get');
        $response = $this->route->dispatch($request);
        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('get-foo', $response->getContent());
    }

    /**
     * Note:
     * User: enna
     * Date: 2024-03-20
     * Time: 17:38
     * @param $path
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

    public function testAllowCrossDomain()
    {
        $this->route->get('foo', function () {
            return 'get-foo';
        })->allowCrossDomain(['some' => 'bar']);

        $request = $this->makeRequest('foo', 'get');
        $response = $this->route->dispatch($request);
        $this->assertEquals('bar', $response->getHeader('some'));
        $this->assertArrayHasKey('Access-Control-Allow-Credentials', $response->getHeader());
    }

    public function testControllerDispatch()
    {
        $this->route->get('foo', 'foo/bar');

        $controller = Mockery::mock(\stdClass::class);

        $this->app->shouldReceive('parseClass')->with('controller', 'Foo')->andReturn($controller->mockery_getName());
        $this->app->shouldReceive('make')->with($controller->mockery_getName(), [], true)->andReturn($controller);
        $controller->shouldReceive('bar')->andReturn('bar');

        $request = $this->makeRequest('foo');
        $response = $this->route->dispatch($request);
        $this->assertEquals('bar', $response->getContent());
    }

    public function testControllerWithMiddleware()
    {
        $this->route->get('foo', 'foo/bar');

        $controller = Mockery::mock(FooClass::class);

//        $controller->middleware = [
//            $this->createMiddleware()->mockery_getName() . ":params1:params2",
//            $this->createMiddleware(0)->mockery_getName() => ['except' => 'bar'],
//            $this->createMiddleware()->mockery_getName()  => ['only' => 'bar'],
//            [
//                'middleware' => [$this->createMiddleware()->mockery_getName(), [new \stdClass()]],
//                'options'    => ['only' => 'bar'],
//            ],
//        ];

        $this->app->shouldReceive('parseClass')->with('controller', 'Foo')->andReturn($controller->mockery_getName());
        $this->app->shouldReceive('make')->with($controller->mockery_getName(), [], true)->andReturn($controller);

        $controller->shouldReceive('bar')->once()->andReturn('bar');

        $request  = $this->makeRequest('foo');
        $response = $this->route->dispatch($request);
        $this->assertEquals('bar', $response->getContent());
    }

    protected function createMiddleware($times = 1)
    {
        $middleware = Mockery::mock(Str::random(5));
        $middleware->shouldReceive('handle')->times($times)->andReturnUsing(function ($request, \Closure $next) {
            return $next($request);
        });

        $this->app->shouldReceive('make')->with($middleware->mockery_getName())->andReturn($middleware);

        return $middleware;
    }

    public function testRedirectDispatch()
    {
        $this->route->redirect('foo', 'http://localhost', 302);

        $request = $this->makeRequest('foo');
        $this->app->shouldReceive('make')->with(Request::class)->andReturn($request);
        $response = $this->route->dispatch($request);

        $this->assertInstanceOf(Redirect::class, $response);
        $this->assertEquals(302, $response->getCode());
        $this->assertEquals('http://localhost', $response->getData());
    }

    public function testDomainBindResponse()
    {
        $this->route->domain('test.domain.com', function () {
            $this->route->get('/', function () {
                return 'Hello,Enna';
            });
        });

        $request  = $this->makeRequest('', 'get', 'test.domain.com');
        $response = $this->route->dispatch($request);

        $this->assertEquals('Hello,Enna', $response->getContent());
        $this->assertEquals(200, $response->getCode());
    }
}

class FooClass
{
    public $middleware = [];

    public function bar()
    {

    }
}