<?php

use Enna\Framework\Container;
use Enna\Framework\App;
use Enna\Framework\Validate;
use Enna\Framework\Event;
use Enna\Framework\Route\Url;
use Enna\Framework\Facade\Route;

if (!function_exists('app')) {
    /**
     * Note: 获取容器中的实例_支持依赖注入
     * Date: 2023-02-10
     * Time: 15:22
     * @param string $name 类名或标识
     * @param array $args 参数
     * @param bool $newInstance 是否每次创建新的实例
     * @return App|object
     */
    function app(string $name = '', array $args = [], bool $newInstance = false)
    {
        return Container::getInstance()->make($name ?: App::class, $args, $newInstance);
    }
}

if (!function_exists('root_path')) {
    /**
     * Note: 获取项目根目录
     * Date: 2023-07-06
     * Time: 16:30
     * @param string $path
     * @return string
     */
    function root_path($path = '')
    {
        return app()->getRootPath() . ($path ? $path . DIRECTORY_SEPARATOR : $path);
    }
}

if (!function_exists('base_path')) {
    /**
     * Note: 获取应用基础目录
     * Date: 2023-07-06
     * Time: 16:33
     * @param string $path
     * @return string
     */
    function base_path($path = '')
    {
        return app()->getBasePath() . ($path ? $path . DIRECTORY_SEPARATOR : $path);
    }
}

if (!function_exists('app_path')) {
    /**
     * Note: 获取当前应用目录
     * Date: 2023-07-06
     * Time: 16:37
     * @param string $path
     * @return string
     */
    function app_path($path = '')
    {
        return app()->getAppPath() . ($path ? $path . DIRECTORY_SEPARATOR : $path);
    }
}

if (!function_exists('config_path')) {
    /**
     * Note: 获取应用配置目录
     * Date: 2023-07-06
     * Time: 16:39
     * @param string $path
     * @return string
     */
    function confi_path($path = '')
    {
        return app()->getConfigPath() . ($path ? $path . DIRECTORY_SEPARATOR : $path);
    }
}

if (!function_exists('public_path')) {
    /**
     * Note: 获取web目录
     * Date: 2023-07-06
     * Time: 16:40
     * @param string $path
     * @return string
     */
    function public_path($path = '')
    {
        return app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . ($path ? $path . DIRECTORY_SEPARATOR : $path);
    }
}

if (!function_exists('runtime_path')) {
    /**
     * Note: 获取应用运行时目录
     * Date: 2023-07-06
     * Time: 16:42
     * @param string $path
     * @return string
     */
    function runtime_path($path = '')
    {
        return app()->getRuntimePath() . ($path ? $path . DIRECTORY_SEPARATOR : $path);
    }
}

if (!function_exists('validate')) {
    /**
     * Note: 生成验证对象
     * Date: 2023-02-10
     * Time: 15:21
     * @param string $validate 验证器
     * @param array $message 验证提示信息
     * @param bool $batch 是否批量
     * @param bool $failException 是否抛出异常
     * @return Validate
     */
    function validate($validate = '', array $message = [], bool $batch = false, bool $failException = true)
    {
        if ($validate === '') {
            $class = new Validate();
        } else {
            $class = new $validate();
        }

        return $class->message($message)->batch($batch)->failException($failException);
    }
}

if (!function_exists('class_basename')) {
    /**
     * Note: 获取类型(不包含命名空间)
     * Date: 2023-05-23
     * Time: 10:03
     * @param mixed $class 类名
     * @return string
     */
    function class_basename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }
}

if (!function_exists('dump')) {
    /**
     * Note: 浏览器友好的变量输出
     * Date: 2023-06-21
     * Time: 17:56
     * @param mixed ...$vars
     * @return void
     */
    function dump(...$vars)
    {
        ob_start();
        var_dump(...$vars);

        $output = ob_get_clean();
        $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);

        if (PHP_SAPI == 'cli') {
            $output = PHP_EOL . $output . PHP_EOL;
        } else {
            if (!extension_loaded('xdebug')) {
                $output = htmlspecialchars($output, ENT_SUBSTITUTE);
            }
            $output = '<pre>' . $output . '</pre>';
        }

        echo $output;
    }
}

if (!function_exists('invoke')) {
    /**
     * Note: 调用反射实例化对象或执行方法,支持依赖注入
     * Date: 2023-07-06
     * Time: 17:22
     * @param $call
     * @param array $args
     * @return mixed|object
     */
    function invoke($call, array $args = [])
    {
        if (is_callable($call)) {
            return Container::getInstance()->invoke($call, $args);
        }

        return Container::getInstance()->invokeClass($call, $args);
    }
}

if (!function_exists('bind')) {
    /**
     * Note: 绑定一个类到容器
     * Date: 2023-07-06
     * Time: 17:24
     * @param string|array $abstract 类标识,接口,闭包,实例
     * @param mixed $concrete 要绑定的类,闭包或实例
     * @return Container
     */
    function bind($abstract, $concrete = null)
    {
        return Container::getInstance()->bind($abstract, $concrete);
    }
}

if (!function_exists('event')) {
    /**
     * Note: 触发事件
     * Date: 2023-07-07
     * Time: 15:38
     * @param $event
     * @param null $args
     * @return mixed
     */
    function event($event, $args = null)
    {
        return Event::trigger($event, $args);
    }
}

if (!function_exists('url')) {
    /**
     * Note: URL生成
     * Date: 2023-07-10
     * Time: 16:47
     * @param string $url 路由地址
     * @param array $vars 变量
     * @param bool|string $suffix 生成URL后缀
     * @param bool|string $domain 域名
     * @return Url
     */
    function url(string $url = '', array $vars = [], $suffix = true, $domain = false)
    {
        return Route::buildUrl($url, $vars)->suffix($suffix)->domain($domain);
    }
}