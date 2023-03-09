<?php
declare(strict_types=1);

namespace Enna\Framework;

use Closure;
use Enna\Framework\Route\Resource;
use LogicException;
use Throwable;
use Enna\Framework\Exception\Handle;


class Middleware
{
    /**
     * 中间件执行队列
     * @var array
     */
    protected $queue = [];

    /**
     * 应用实例
     * @var App
     */
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * Note: 加载中间件
     * Date: 2022-09-20
     * Time: 18:40
     * @param array $middlewares 中间件
     * @param string $type 类型
     * @return void
     */
    public function import(array $middlewares = [], string $type = 'global')
    {
        foreach ($middlewares as $middleware) {
            $this->add($middleware, $type);
        }
    }


    /**
     * Note: 注册中间件
     * Date: 2022-09-20
     * Time: 18:42
     * @param mixed $middleware 中间件
     * @param string $type 类型
     * @return void
     */
    public function add($middleware, string $type = 'global')
    {
        $middleware = $this->parse($middleware, $type);

        if (!empty($middleware)) {
            $this->queue[$type][] = $middleware;
            $this->queue[$type] = array_unique($this->queue[$type], SORT_REGULAR);
        }
    }

    /**
     * Note: 注册路由中间件
     * Date: 2022-10-10
     * Time: 18:17
     * @param $middleware
     * @return void
     */
    public function route($middleware)
    {
        $this->add($middleware, 'route');
    }

    /**
     * Note: 注册控制器中间件
     * Date: 2022-10-10
     * Time: 18:17
     * @param mixed $middleware
     * @return void
     */
    public function controller($middleware)
    {
        $this->add($middleware, 'controller');
    }

    /**
     * Note: 解析中间件
     * Date: 2022-09-21
     * Time: 16:30
     * @param mixed $middleware
     * @param string $type
     * @return array
     */
    protected function parse($middleware, string $type): array
    {
        if (is_array($middleware)) {
            [$middleware, $params] = $middleware;
        }

        if ($middleware instanceof Closure) {
            return [$middleware, $params ?? []];
        }

        if (!is_string($middleware)) {
            throw new \InvalidArgumentException('The middleware is invalid');
        }

        $alias = $this->app->config->get('middleware.alias', []);
        if (isset($alias[$middleware])) {
            $middleware = $alias[$middleware];
        }

        if (is_array($middleware)) {
            $this->add($middleware, $type);
            return [];
        }

        return [[$middleware, 'handle'], $params ?? []];

    }

    /**
     * Note: 调度管道
     * Date: 2022-09-21
     * Time: 15:40
     * @param string $type 中间件类型
     * @return Pipeline
     */
    public function pipeline($type = 'global')
    {
        $pipes = array_map(function ($middleware) {
            return function ($request, $next) use ($middleware) {
                [$call, $params] = $middleware;
                if (is_array($call) && is_string($call[0])) {
                    $call = [$this->app->make($call[0]), $call[1]];
                }

                $response = call_user_func($call, $request, $next, ...$params);

                if (!$response instanceof Response) {
                    throw new LogicException('The middleware must return Response instance');
                }
                return $response;
            };
        }, $this->sortMiddleware($this->queue[$type] ?? []));

        $pipeline = new Pipeline();
        return $pipeline->through($pipes)->whenException([$this, 'handleException']);
    }

    /**
     * Note: 异常处理
     * Date: 2022-12-02
     * Time: 18:19
     * @param Request $passalbe
     * @param Throwable $e
     * @return Response
     */
    public function handleException($passable, Throwable $e)
    {
        /** @var Handle $handler */
        $handler = $this->app->make(Handle::class);

        $handler->report($e);

        return $handler->render($passable, $e);
    }

    /**
     * Note: 中间件排序
     * Date: 2022-09-21
     * Time: 17:17
     * @param array $middlewares 中间件
     * @return array
     */
    protected function sortMiddleware(array $middlewares)
    {
        $priority = $this->app->config->get('middleware.priority', []);
        usort($middlewares, function ($a, $b) use ($priority) {
            $aPriority = $this->getMiddlewarePriority($priority, $a);
            $bPriority = $this->getMiddlewarePriority($priority, $b);

            return $bPriority - $aPriority;
        });

        return $middlewares;
    }

    /**
     * Note: 获取中间件优先级
     * Date: 2022-09-21
     * Time: 17:28
     * @param array $priority 中间件优先级信息
     * @param array $middleware 中间件
     * @return int
     */
    protected function getMiddlewarePriority($priority, $middleware)
    {
        [$call] = $middleware;
        if (is_array($call) && is_string($call[0])) {
            $index = array_search($call[0], array_reverse($priority));

            if ($index === false) {
                return -1;
            } else {
                return $index;
            }
        }
        return -1;
    }

    /**
     * Note: 结束中间件调度
     * Date: 2023-03-01
     * Time: 14:15
     * @param Response $response
     */
    public function end(Response $response)
    {
        foreach ($this->queue as $queue) {
            foreach ($queue as $middleware) {
                [$call] = $middleware;
                if (is_array($call) && is_string($call[0])) {
                    $instance = $this->app->make($call[0]);
                    if (method_exists($instance, 'end')) {
                        $instance->end($response);
                    }
                }
            }
        }
    }
}