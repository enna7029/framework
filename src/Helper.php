<?php

use Enna\Framework\Container;
use Enna\Framework\App;
use Enna\Framework\Request;
use Enna\Framework\Validate;
use Enna\Framework\Event;
use Enna\Framework\Route\Url;
use Enna\Framework\Facade\Route;
use Enna\Framework\Facade\Session;
use Enna\Framework\Response;
use Enna\Framework\Exception\HttpResponseException;
use Enna\Framework\Exception\HttpException;

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

if (!function_exists('halt')) {
    function halt(...$vars)
    {
        dump(...$vars);

        throw new HttpResponseException(Response::create());
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

if (!function_exists('parse_name')) {
    /**
     * Note: 字符串命名风格转换
     * Date: 2023-08-09
     * Time: 15:18
     * @param string $name 字符串
     * @param int $type 转换类型
     * @param bool $ucfirst 首字母是否大写
     * @return string
     */
    function parse_name(string $name = '', int $type = 0, bool $ucfirst = true)
    {
        if ($type) {
            $name = preg_replace_callback('/_(a-zA-Z)/', function ($match) {
                return strtolower($match[1]);
            }, $name);

            return $ucfirst ? ucfirst($name) : lcfirst($name);
        }

        return strtolower(trim(str_replace('/[A-Z]/', '_\\0', $name), '_'));
    }
}

if (!function_exists('request')) {
    /**
     * Note: 获取当前Request对象实例
     * Date: 2023-08-09
     * Time: 18:14
     * @return Request
     */
    function request()
    {
        return app('request');
    }
}

if (!function_exists('input')) {
    /**
     * Note: 获取输入数据 支持默认值和过滤
     * Date: 2023-08-09
     * Time: 17:58
     * @param string $key 获取的变量名
     * @param mixed $default 默认值
     * @param string $filter 过滤方法
     * @return mixed
     */
    function input(string $key = '', $default = null, $filter = '')
    {
        if (strpos($key, '?') === 0) {
            $key = substr($key, 1);
            $has = true;
        }

        if ($pos = strpos($key, '.')) {
            $method = substr($key, 0, $pos);
            if (in_array($method, ['get', 'post', 'patch', 'delete', 'route', 'param', 'request', 'session', 'cookie', 'server', 'env', 'path', 'file'])) {
                $key = substr($key, $pos + 1);
                if ($method == 'server' && is_null($default)) {
                    $default = '';
                }
            } else {
                $method = 'param';
            }
        } else {
            $method = 'param';
        }

        return isset($has) ? request()->has($key, $method) : request()->$method($key, $default, $filter);
    }
}

if (!function_exists('response')) {
    /**
     * Note: 创建普通Response对象实例
     * Date: 2023-08-16
     * Time: 12:02
     * @param mixed $data 返回的数据
     * @param int $code 返回的状态码
     * @param array $header 头部
     * @param string $type 类型
     * @return Response
     */
    function response($data = '', $code = 200, $header = [], $type = 'html')
    {
        return Response::create($data, $type, $code)->header($header);
    }
}

if (!function_exists('json')) {
    /**
     * Note: 获取Enna\Framework\Response\Json对象实例
     * Date: 2023-08-16
     * Time: 11:49
     * @param array $data 返回的数据
     * @param int $code 返回的状态码
     * @param array $header 头部
     * @param array $options 参数
     * @return \Enna\Framework\Response\Json
     */
    function json($data = [], $code = 200, $header = [], $options = [])
    {
        return Response::create($data, 'json', $code)->header($header)->options($options);
    }
}

if (!function_exists('redirect')) {
    /**
     * Note: 获取\Enna\Framework\Response\Redirect对象实例
     * Date: 2023-08-16
     * Time: 15:09
     * @param string $url 重定向地址
     * @param int $code 状态码
     * @return \Enna\Framework\Response\Redirect
     */
    function redirect(string $url = '', int $code = 302)
    {
        return Response::create($url, 'redirect', $code);
    }
}

if (!function_exists('download')) {
    /**
     * Note: 获取\Enna\Framework\Response\File对象实例
     * Date: 2023-08-16
     * Time: 15:31
     * @param string $filename 要下载的文件
     * @param string $name 显示文件名
     * @param bool $content 是否为内容
     * @param int $expire 有效期(秒)
     * @return Enna\Framework\Response\File
     */
    function download(string $filename = '', string $name = '', bool $content = false, int $expire = 180)
    {
        return Response::create($filename, 'file')->name($name)->isContent($content)->expire($expire);
    }
}

if (!function_exists('jsonp')) {
    /**
     * Note: 获取Enna\Framework\Response\Jsonp对象实例
     * Date: 2023-08-21
     * Time: 16:32
     * @param array $data 返回的数据
     * @param int $code 状态码
     * @param array $header 头部
     * @param array $options 参数
     * @return Enna\Framework\Response\Jsonp
     */
    function jsonp($data = [], $code = 200, $header = [], $options = [])
    {
        return Response::create($data, 'jsonp', $code)->header($header)->options($options);
    }
}

if (!function_exists('xml')) {
    /**
     * Note: 获取\Enna\Framework\Response\Xml 对象实例
     * Date: 2023-08-21
     * Time: 16:34
     * @param array $data 返回的数据
     * @param int $code 状态码
     * @param array $header 头部
     * @param array $options 参数
     * @return Enna\Framework\Response\Xml
     */
    function xml($data = [], $code = 200, $header = [], $options = [])
    {
        return Response::create($data, 'xml', $code)->header($header)->options($options);
    }
}

if (!function_exists('session')) {
    /**
     * Note: Session管理
     * Date: 2023-08-16
     * Time: 15:15
     * @param string $name session名称
     * @param string $value session值
     * @return mixed
     */
    function session($name = '', $value = '')
    {
        if (is_null($name)) { //清楚
            Session::clear();
        } elseif ($name === '') { //获取所有
            return Session::all();
        } elseif (is_null($value)) { //删除
            Session::delete($name);
        } elseif ($value === '') { //判断或获取
            return strpos($name, '?') === 0 ? Session::has(substr($name, 1)) : Session::get($name);
        } else { //设置
            Session::set($name, $value);
        }
    }
}

if (!function_exists('abort')) {
    /**
     * Note: 抛出HTTP异常
     * Date: 2023-08-23
     * Time: 10:24
     * @param int|Response $code 状态码|Response对象实例
     * @param string $message 错误信息
     * @param array $header 参数
     * @throws \Enna\Framework\Exception\HttpResponseException|\Enna\Framework\Exception\HttpException
     */
    function abort($code, string $message = '', array $header = [])
    {
        if ($code instanceof Response) {
            throw new HttpResponseException($code);
        } else {
            throw new HttpException($code, $message, null, $header);
        }
    }
}

