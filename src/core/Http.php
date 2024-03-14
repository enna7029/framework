<?php
declare(strict_types=1);

namespace Enna\Framework;

use Enna\Framework\Event\AppInit;
use Enna\Framework\Exception\Handle;
use Predis\Command\Redis\DISCARD;
use Throwable;
use Enna\Framework\Event\HttpRun;
use Enna\Framework\Event\HttpEnd;
use Enna\Framework\Event\RouteLoaded;

/**
 * Web应用管理类
 * Class Http
 * @package Enna\Framework
 */
class Http
{
    /**
     * app实例
     * @var App
     */
    protected $app;

    /**
     * 应用名称
     * @var string
     */
    protected $name;

    /**
     * 应用路径
     * @var string
     */
    protected $path;

    /**
     * 路由路径
     * @var string
     */
    protected $routePath;

    /**
     * 是否绑定应用
     * @var bool
     */
    protected $isBind;

    public function __construct(App $app)
    {
        $this->app = $app;

        $this->routePath = $this->app->getRootPath() . 'route' . DIRECTORY_SEPARATOR;
    }

    /**
     * Note: 设置应用名称
     * Date: 2023-07-06
     * Time: 15:56
     * @param string $name
     * @return $this
     */
    public function name(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Note: 获取应用名称
     * Date: 2023-07-06
     * Time: 15:58
     * @return string
     */
    public function getName()
    {
        return $this->name ?: '';
    }

    /**
     * Note: 设置应用目录
     * Date: 2023-07-06
     * Time: 15:59
     * @param string $path 应用目录
     * @return $this
     */
    public function path(string $path)
    {
        if (substr($path, -1) != DIRECTORY_SEPARATOR) {
            $path .= DIRECTORY_SEPARATOR;
        }

        $this->path = $path;

        return $this;
    }

    /**
     * Note: 获取应用路径
     * Date: 2023-07-06
     * Time: 16:18
     * @return string
     */
    public function getPath()
    {
        return $this->path ?: '';
    }

    /**
     * Note: 获取路由目录
     * Date: 2023-07-06
     * Time: 16:19
     * @return string
     */
    public function getRoutePath()
    {
        return $this->routePath;
    }

    /**
     * Note: 设置路由目录
     * Date: 2023-07-06
     * Time: 16:20
     * @param string $path 路由定义目录
     * @return void
     */
    public function setRoutePath(string $path)
    {
        $this->routePath = $path;
    }

    /**
     * Note: 设置应用绑定
     * Date: 2024-03-05
     * Time: 10:16
     * @param bool $bind 是否绑定
     * @return $this
     */
    public function setBind(bool $bind = true)
    {
        $this->isBind = $bind;

        return $this;
    }

    /**
     * Note: 是否绑定应用
     * Date: 2024-03-05
     * Time: 10:17
     * @return bool
     */
    public function isBind()
    {
        return $this->isBind;
    }

    /**
     * Note: 执行应用程序
     * Date: 2022-09-17
     * Time: 17:22
     * @return Response
     */
    public function run(): Response
    {
        //初始化
        $this->initialize();

        //实例化request对象
        $request = $this->app->make('request', [], true);
        $this->app->instance('request', $request);

        try {
            $response = $this->runWithRequest($request);
        } catch (Throwable $e) {
            $this->reportException($e);

            $response = $this->renderException($request, $e);
        }

        return $response;
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
     * Note: 加载全局中间件
     * Date: 2022-09-20
     * Time: 18:36
     * @return void
     */
    protected function loadMiddleware(): void
    {
        if (is_file($this->app->getAppPath() . 'middleware.php')) {
            $this->app->middleware->import(include $this->app->getAppPath() . 'middleware.php');
        }
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
     * Note: 使用异常处理类记录异常
     * Date: 2022-09-20
     * Time: 17:11
     * @param Throwable $e
     * @return void
     */
    protected function reportException(Throwable $e)
    {
        $this->app->make(Handle::class)->report($e);
    }

    /**
     * Note: 使用异常处理类将异常渲染到HTTP响应中
     * Date: 2022-09-20
     * Time: 17:15
     * @param Request $request
     * @param Throwable $e
     * @return Response
     */
    protected function renderException($request, Throwable $e)
    {
        return $this->app->make(Handle::class)->render($request, $e);
    }

    /**
     * Note: http请求的结束
     * Date: 2022-12-09
     * Time: 16:44
     * @param Response $response
     * @return void
     */
    public function end(Response $response)
    {
        $this->app->event->trigger(HttpEnd::class, $response);

        $this->app->middleware->end($response);

        $this->app->log->save();
    }

}