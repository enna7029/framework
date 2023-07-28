<?php
declare(strict_types=1);

namespace Enna\Framework\Route\Dispatch;

use Enna\Framework\App;
use Enna\Framework\Exception\ClassNotFoundException;
use Enna\Framework\Route\Dispatch;
use Enna\Framework\Exception\HttpException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionException;
use Enna\Framework\Helper\Str;

class Controller extends Dispatch
{
    /**
     * 控制器名
     * @var string
     */
    protected $controller;

    /**
     * 操作名
     * @var string
     */
    protected $action;

    public function init(App $app)
    {
        parent::init($app);

        $result = $this->dispatch;
        if (is_string($result)) {
            $result = explode('/', $result);
        }

        //控制器名
        $controller = strip_tags($result[0] ?: $this->rule->config('default_controller'));
        if (strpos($controller, '.')) {
            $pos = strpos($controller, '.');
            $this->controller = substr($controller, 0, $pos) . '.' . Str::studly(substr($controller, $pos + 1));
        } else {
            $this->controller = Str::studly($controller);
        }
        //操作名
        $this->action = strip_tags($result[1] ?: $this->rule->config('default_action'));

        //设置当前请求的控制器,操作
        $this->request->setController($this->controller)->setAction($this->action);
    }

    public function exec()
    {
        try {
            $instance = $this->controller($this->controller);
        } catch (ClassNotFoundException $e) {
            throw new HttpException(404, 'controller not exists:' . $e->getClass());
        }
        $this->registerControllerMiddleware($instance);

        return $this->app->middleware->pipeline('controller')
            ->send($this->request)
            ->then(function () use ($instance) {
                $suffix = $this->rule->config('action_suffix');
                $action = $this->action . $suffix;

                if (is_callable([$instance, $action])) {
                    $vars = $this->request->param();

                    try {
                        $reflect = new ReflectionMethod($instance, $action);

                        $actionName = $reflect->getName();
                        if ($suffix) {
                            $actionName = substr($actionName, 0, -strlen($suffix));
                        }
                        $this->request->setAction($actionName);
                    } catch (ReflectionException $e) {
                        $reflect = new ReflectionMethod($instance, '__call');
                        $vars = [$action, $vars];

                        $this->request->setAction($action);
                    }
                } else {
                    throw new HttpException(404, 'method not exists:' . get_class($instance) . '->' . $action . '()');
                }

                $data = $this->app->invokeReflectMethod($instance, $reflect, $vars);
         
                return $data;
            });
    }

    /**
     * Note: 注册控制器中间件
     * Date: 2022-10-09
     * Time: 18:40
     * @param object $controller 控制器实例
     * @return void
     */
    protected function registerControllerMiddleware($controller)
    {
        $class = new ReflectionClass($controller);

        if ($class->hasProperty('middleware')) {
            $reflectionProperty = $class->getProperty('middleware');
            $reflectionProperty->setAccessible(true);

            $middlewares = $reflectionProperty->getValue($controller);
            $action = $this->request->action();

            foreach ($middlewares as $key => $val) {
                if (!is_int($key)) {
                    $middleware = $key;
                    $options = $val;
                } else {
                    $middleware = $val;
                    $options = [];
                }

                if (isset($options['only']) && !in_array($action, $this->parseAction($options['only']))) {
                    continue;
                } elseif (isset($options['except']) && in_array($action, $this->parseAction($options['except']))) {
                    continue;
                }

                if (is_string($middleware) && strpos($middleware, ':')) {
                    $middleware = explode(':', $middleware);
                    if (count($middleware)) {
                        $middleware = [$middleware[0], array_slice($middlewares, 1)];
                    }
                }

                $this->app->middleware->controller($middleware);
            }
        }
    }

    /**
     * Note: 实例化控制器
     * Date: 2022-10-09
     * Time: 18:22
     * @param string $name
     * @return object
     */
    public function controller(string $name)
    {
        $suffix = $this->rule->config('controller_suffix') ? 'Controller' : ''; //是否使用控制器后缀
        $controllerLayer = $this->rule->config('controller_layer') ?: 'controller'; //控制器层名称
        $emptyController = $this->rule->config('empty_controller') ?: 'Error'; //空控制器名

        $class = $this->app->parseClass($controllerLayer, $name);

        if (class_exists($class)) {
            return $this->app->make($class, [], true);
        } elseif ($emptyController && class_exists($emptyClass = $this->app->parseClass($controllerLayer, $emptyController))) {
            return $this->app->make($emptyClass, [], true);
        }

        throw new ClassNotFoundException('class not exists:' . $class, $class);
    }

    protected function parseAction($actions)
    {
        return array_map(function ($item) {
            return strtolower($item);
        }, is_string($actions) ? explode(',', $actions) : $actions);
    }
}