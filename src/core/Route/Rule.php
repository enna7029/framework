<?php
declare(strict_types=1);

namespace Enna\Framework\Route;

use Closure;
use Enna\Framework\Route;
use Enna\Framework\Middleware\AllowCrossDomain;
use \Enna\Framework\Request;
use Enna\Framework\Route\Dispatch\Callback as CallbackDispatch;
use Enna\Framework\Route\Dispatch\Controller as ControllerDispatch;

abstract class Rule
{
    /**
     * 路由标识
     * @var string
     */
    protected $name;

    /**
     * 所在域名
     * @var string
     */
    protected $domain;

    /**
     * 路由对象
     * @var Route
     */
    protected $router;

    /**
     * 路由所属分组
     * @var RuleGroup
     */
    protected $parent;

    /**
     * 路由规则
     * @var mixed
     */
    protected $rule;

    /**
     * 路由地址
     * @var string|Closure
     */
    protected $route;

    /**
     * 请求类型
     * @var string
     */
    protected $method;

    /**
     * 路由变量
     * @var array
     */
    protected $vars = [];

    /**
     * 路由参数
     * @var array
     */
    protected $option = [];

    /**
     * 路由变量规则
     * @var array
     */
    protected $pattern = [];

    /**
     * Note: 设置单个路由参数
     * Date: 2022-10-26
     * Time: 14:47
     * @param array $option 参数
     * @return $this
     */
    public function option(array $option)
    {
        $this->option = array_merge($this->option, $option);

        return $this;
    }

    /**
     * Note: 设置单个路由参数
     * Date: 2022-10-26
     * Time: 14:46
     * @param string $name 参数名
     * @param mixed $value 值
     * @return $this
     */
    public function setOption(string $name, $value)
    {
        $this->option[$name] = $value;

        return $this;
    }

    /**
     * Note: 附加路由隐藏参数
     * Date: 2022-10-25
     * Time: 18:09
     * @param array $append 参数
     * @return $thisF
     */
    public function append(array $append = [])
    {
        $this->option['append'] = $append;

        return $this;
    }

    /**
     * Note: 设置标识
     * Date: 2022-10-25
     * Time: 18:10
     * @param string $name 标识名
     * @return $this
     */
    public function name(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Note: 检查后缀
     * Date: 2022-10-26
     * Time: 14:49
     * @param string $ext 后缀
     * @return $this
     */
    public function ext(string $ext = '')
    {
        return $this->setOption('ext', $ext);
    }

    /**
     * Note: 检查是否为HTTPS
     * Date: 2022-10-26
     * Time: 14:52
     * @param bool $https 是否为HTTPS
     * @return $this
     */
    public function https(bool $https = true)
    {
        return $this->setOption('https', $https);
    }

    /**
     * Note: 设置路由中间件
     * Date: 2022-10-26
     * Time: 14:59
     * @param string|array|Closure $middleware 中间件
     * @param mixed ...$params 中间件参数
     * @return $this
     */
    public function middleware($middleware, ...$params)
    {
        if (empty($params) && is_array($middleware)) {
            $this->option['middleware'] = $middleware;
        } else {
            foreach ((array)$middleware as $item) {
                $this->option['middleware'][] = [$item, $params];
            }
        }

        return $this;
    }

    /**
     * Note: 设置变量规则
     * Date: 2022-10-25
     * Time: 18:23
     * @param array $pattern 规则
     * @return $this
     */
    public function pattern(array $pattern)
    {
        $this->pattern = array_merge($this->pattern, $pattern);

        return $this;
    }

    /**
     * Note: 获取路由配置
     * Date: 2023-06-21
     * Time: 10:31
     * @param string $name 变量名
     * @return mixed
     */
    public function config(string $name = '')
    {
        return $this->router->config($name);
    }

    /**
     * Note: 获取路由对象
     * Date: 2022-10-09
     * Time: 17:59
     * @return Route
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * Note: 获取路由标识
     * Date: 2022-10-22
     * Time: 16:56
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Note: 获取当前路由地址
     * Date: 2022-10-22
     * Time: 16:54
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Note: 获取当前路由规则
     * Date: 2022-10-22
     * Time: 16:57
     * @return void
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * Note: 获取当前路由的变量
     * Date: 2022-10-22
     * Time: 16:59
     * @return array
     */
    public function getVars()
    {
        return $this->vars;
    }

    /**
     * Note: 获取以上级的对象
     * Date: 2022-10-22
     * Time: 16:59
     * @return $this|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Note: 获取路由所在域名
     * Date: 2022-10-22
     * Time: 17:00
     * @return string
     */
    public function getDomain()
    {
        return $this->domain ?: $this->parent->getDomain();
    }

    /**
     * Note: 获取当前路由的请求类型
     * Date: 2022-10-22
     * Time: 17:02
     * @return string
     */
    public function getMethod()
    {
        return strtolower($this->method);
    }

    /**
     * Note: 获取路由参数定义
     * Date: 2022-10-22
     * Time: 17:03
     * @param string $name 参数名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function getOption(string $name = '', $default = null)
    {
        $option = $this->option;

        if ($name === '') {
            return $option;
        }

        return $option[$name] ?? $default;
    }

    /**
     * Note: 获取变量规则定义
     * Date: 2022-10-29
     * Time: 15:07
     * @param string $name 变量名称
     * @return mixed
     */
    public function getPattern(string $name = '')
    {
        $pattern = $this->pattern;

        if ($name == '') {
            return $pattern;
        }

        return $pattern[$name] ?? null;
    }


    /**
     * Note: 解析URL的pathinfo路径
     * Date: 2022-10-09
     * Time: 16:47
     * @param string $url
     * @return array
     */
    public function parseUrlPath(string $url)
    {
        $url = str_replace('|', '/', $url);
        $url = trim($url, '/');

        if (strpos($url, '/')) {
            $path = explode('/', $url);
        } else {
            $path = [$url];
        }

        return $path;
    }

    /**
     * Note:
     * User: enna
     * Date: 2022-10-27
     * Time: 10:34
     * @param array $header 自定义的header
     * @return $this
     */
    public function allowCrossDomain(array $header = [])
    {
        return $this->middleware(AllowCrossDomain::class, $header);
    }

    /**
     * Note: 检查路由的选项
     * Date: 2022-10-29
     * Time: 10:30
     * @param array $option 路由选项
     * @param Request $request 请求对象
     * @return bool
     */
    protected function checkOption(array $option, Request $request)
    {
        //请求类型检测
        if (!empty($option['method'])) {
            if (is_string($option['method']) && stripos($option['method'], $request->method()) === false) {
                return false;
            }
        }

        //ajax,json检测
        foreach (['ajax', 'json'] as $item) {
            if (isset($option[$item])) {
                $call = 'is' . ucfirst($item);
                if (!$request->$call) {
                    return false;
                }
            }
        }

        //域名检测
        if (isset($option['domain']) && !in_array($option['domain'], [$request->host(true), $request->subDomain()])) {
            return false;
        }

        //HTTPS检测
        if (isset($option['https']) && $option['https'] && !$request->isSsl()) {
            return false;
        }

        return true;
    }

    /**
     * Note: 解析匹配到的路由规则
     * Date: 2022-10-29
     * Time: 17:36
     * @param Request $request 请求对象
     * @param string $rule 路由规则
     * @param mixed $route 路由地址
     * @param string $url URL地址
     * @param array $option 选项
     * @param array $matches 匹配到的变量
     */
    public function parseRule(Request $request, string $rule, $route, string $url, array $option = [], array $matches = [])
    {
        if (is_string($route) && isset($option['prefix'])) {
            $route = $option['prefix'] . $route;
        }
        $search = $replace = [];
        foreach ($matches as $key => $value) {
            $search[] = '<' . $key . '>';
            $replace[] = $value;

            $search[] = ':' . $key;
            $replace[] = $value;
        }

        if (is_string($route)) {
            $route = str_replace($search, $replace, $route);
        }

        $this->vars = $matches;

        //发起路由调度
        return $this->dispatch($request, $route, $option);
    }

    /**
     * Note: 发起路由调度
     * Date: 2022-10-29
     * Time: 17:41
     * @param Request $request 请求对象
     * @param mixed $route 路由地址
     * @param array $option 路由选项
     * @return Dispatch
     */
    protected function dispatch(Request $request, $route, array $option)
    {
        if (is_subclass_of($route, Dispatch::class)) {
            $result = $route($request, $this, $route, $this->vars);
        } elseif ($route instanceof Closure) {
            $result = new CallbackDispatch($request, $this, $route, $this->vars);
        } else {
            //路由到控制器/操作
            $result = $this->dispatchController($request, $route);
        }

        return $result;
    }

    /**
     * Note: 解析URL地址为 模块/控制器/操作
     * Date: 2022-11-09
     * Time: 18:20
     * @param Request $request 请求对象
     * @param string $route 路由地址
     * @return ControllerDispatch
     */
    public function dispatchController(Request $request, string $route)
    {
        $path = $this->parseUrlPath($route);

        $action = array_pop($path);
        $controller = !empty($path) ? array_pop($path) : null;

        return new ControllerDispatch($request, $this, [$controller, $action], $this->vars);
    }

    public function __debugInfo(): ?array
    {
        return [
            'name' => $this->name,
            'rule' => $this->rule,
            'route' => $this->route,
            'method' => $this->method,
            'vars' => $this->vars,
            'option' => $this->option,
            'pattern' => $this->pattern,
        ];
    }

}