<?php
declare(strict_types=1);

namespace Enna\Framework;

use Enna\Framework\Event\AppInit;
use Enna\Framework\Exception\Handle;
use Throwable;
use Enna\Framework\Event\HttpRun;
use Enna\Framework\Event\RouteLoaded;

class Http
{
    /**
     * app实例
     * @var App
     */
    protected $app;

    /**
     * 路由路径
     * @var string
     */
    protected $routePath;

    public function __construct(App $app)
    {
        $this->app = $app;

        $this->routePath = $this->app->getRootPath() . 'route' . DIRECTORY_SEPARATOR;
    }

    /**
     * Note: 执行应用程序
     * Date: 2022-09-17
     * Time: 17:22
     * @return Response
     */
    public function run(): Response
    {
        $this->initialize();
        $request = $this->app->make('request', [], true);
        $this->app->instance('request', $request);

        try {
            $response = $this->runWithRequest($request);
        } catch (Throwable $e) {
            $this->reportException($e);

            $response = $this->renderException($e);
        }

        return $response;
    }

    /**
     * Note: 使用异常处理类记录异常
     * Date: 2022-09-20
     * Time: 17:11
     * @param Throwable $e
     */
    protected function reportException(Throwable $e)
    {
        $this->app->make(Handle::class)->report($e);
    }

    /**
     * Note: 使用异常处理类将异常渲染到HTTP响应中
     * Date: 2022-09-20
     * Time: 17:15
     * @param Throwable $e
     */
    protected function renderException(Throwable $e)
    {
        return $this->app->make(Handle::class)->render($e);
    }

    /**
     * Note: 初始化
     * Date: 2022-09-17
     * Time: 17:20
     */
    public function initialize()
    {
        if (!$this->app->initialized()) {
            $this->app->initialize();
        }
    }

    /**
     * Note: 执行请求
     * Date: 2022-09-17
     * Time: 17:21
     * @param Request $request
     * @return mixed
     */
    public function runWithRequest(Request $request)
    {
        //加载全局中间件
        $this->loadMiddleware();

        //监听HttpRun
        $this->app->event->trigger(HttpRun::class);

        return $this->app->middleware->pipeline()
            ->send($request)
            ->then(function ($request) {
                return $this->dispatchToRoute($request);
            });
    }

    /**
     * Note: 将请求发送到路由
     * Date: 2022-09-28
     * Time: 16:14
     * @param $request
     */
    protected function dispatchToRoute($request)
    {
        $withRoute = $this->app->config->get('app.with_route', true) ? function () {
            $this->loadRoutes();
        } : null;

        return $this->app->route->dispatch($request, $withRoute);
    }

    /**
     * Note: 加载路由
     * Date: 2022-09-28
     * Time: 16:15
     * @return void
     */
    protected function loadRoutes(): void
    {
        $routePath = $this->getRoutePath();
 
        if (is_dir($routePath)) {
            $files = glob($routePath . '*.php');
            foreach ($files as $file) {
                include $file;
            }
        }

        $this->app->event->trigger(RouteLoaded::class);
    }


    /**
     * Note:
     * User: enna
     * Date: 2022-09-20
     * Time: 18:36
     * @return void
     */
    protected function loadMiddleware(): void
    {
        if (is_file($this->app->getCorePath() . 'middleware.php')) {
            $this->app->middleware->import(include $this->app->getCorePath() . 'middleware.php');
        }
    }

    /**
     * Note: 获取路由目录
     * Date: 2022-09-28
     * Time: 16:16
     * @return string
     */
    public function getRoutePath()
    {
        return $this->routePath;
    }

}