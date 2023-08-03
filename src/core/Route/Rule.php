<?php
declare(strict_types=1);

namespace Enna\Framework\Route;

use Closure;
use Enna\Framework\Route;
use Enna\Framework\Request;
use Enna\Framework\Middleware\AllowCrossDomain;
use Enna\Framework\Middleware\CheckRequestCache;
use Enna\Framework\Middleware\FormTokenCheck;
use Enna\Framework\Route\Dispatch\Callback as CallbackDispatch;
use Enna\Framework\Route\Dispatch\Controller as ControllerDispatch;

/**
 * 路由规则基础类
 * Class Rule
 * @package Enna\Framework\Route
 */
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
     * 需要和分组合并的路由参数
     * @var string[]
     */
    protected $mergeOptions = ['model', 'append', 'middleware'];

    abstract public function check(Request $request, string $url, bool $completeMatch = false);

    /**
     * Note: 批量设置路由参数
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
     * @param string $ext URL后缀
     * @return $this
     */
    public function ext(string $ext = '')
    {
        return $this->setOption('ext', $ext);
    }

    /**
     * Note: 检查禁止后缀
     * Date: 2023-07-12
     * Time: 11:37
     * @param string $ext URL后缀
     * @return $this
     */
    public function denyExt(string $ext = '')
    {
        return $this->setOption('deny_ext', $ext);
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
     * Note: 检查域名
     * Date: 2023-07-12
     * Time: 13:58
     * @param string $domain 域名
     * @return $this
     */
    public function domain(string $domain)
    {
        $this->domain = $domain;

        return $this->setOption('domain', $domain);
    }

    /**
     * Note: 设置路由完整匹配
     * Date: 2023-07-12
     * Time: 14:00
     * @param bool $match 是否完整匹配
     * @return $this
     */
    public function completeMatch(bool $match = true)
    {
        return $this->setOption('complete_match', $match);
    }

    /**
     * Note: 是否去除URL最后的斜线
     * Date: 2023-07-13
     * Time: 18:09
     * @param bool $remove 是否去除最后的斜线
     * @return $this
     */
    public function removeSlash(bool $remove = false)
    {
        return $this->setOption('remove_slash', $remove);
    }

    /**
     * Note: 设置路由请求类型
     * Date: 2023-07-19
     * Time: 14:40
     * @param string $method 请求类型
     * @return $this
     */
    public function method(string $method)
    {
        return $this->setOption('method', strtolower($method));
    }

    /**
     * Note: 路由到一个模板地址 需要额外传入的模板变量
     * Date: 2023-08-01
     * Time: 18:23
     * @param array $view 视图
     * @return $this
     */
    public function view(array $view = [])
    {
        return $this->setOption('view', $view);
    }

    /**
     * Note: 检查是否为ajax请求
     * Date: 2023-08-01
     * Time: 18:24
     * @param bool $ajax 是否为ajax
     * @return $this
     */
    public function ajax(bool $ajax = true)
    {
        return $this->setOption('ajax', $ajax);
    }

    /**
     * Note: 检查是否为PJAX请求
     * Date: 2023-08-01
     * Time: 18:25
     * @param bool $pjax 是否为PJAX
     * @return $this
     */
    public function pjax(bool $pjax = true)
    {
        return $this->setOption('ajax', $ajax);
    }

    /**
     * Note: 检查是否为JSON请求
     * Date: 2023-08-01
     * Time: 18:25
     * @param bool $json 是否为json
     * @return $this
     */
    public function json(bool $json = true)
    {
        return $this->setOption('json', $json);
    }

    /**
     * Note: 检查URL分隔符
     * Date: 2023-08-01
     * Time: 18:31
     * @param string $depr URL分隔符
     * @return $this
     */
    public function depr(string $depr)
    {
        return $this->setOption('param_depr', $depr);
    }

    /**
     * Note: 绑定模型
     * Date: 2023-07-12
     * Time: 18:13
     * @param array|string|Closure $var 路由变量名 多个使用&分割
     * @param string|Closure $model 绑定模型类
     * @param bool $exception 是否抛出异常
     * @return $this
     */
    public function model($var, $model = null, bool $exception = true)
    {
        if ($var instanceof Closure) {
            $this->option['model'][] = $var;
        } elseif (is_array($var)) {
            $this->option['model'] = $var;
        } elseif (is_null($model)) {
            $this->option['model']['id'] = [$var, true];
        } else {
            $this->option['model'][$var] = [$model, $exception];
        }

        return $this;
    }

    /**
     * Note: 绑定验证
     * Date: 2023-07-28
     * Time: 10:41
     * @param mixed $validate 验证器类
     * @param string $scene 验证场景
     * @param arary $message 验证提示
     * @param bool $batch 批量验证
     * @return $this
     */
    public function validate($validate, string $scene = null, array $message = [], bool $batch = false)
    {
        $this->option['validate'] = [$validate, $scene, $message, $batch];

        return $this;
    }

    /**
     * Note: 附加路由隐藏参数
     * Date: 2022-10-25
     * Time: 18:09
     * @param array $append 参数
     * @return $this
     */
    public function append(array $append = [])
    {
        $this->option['append'] = $append;

        return $this;
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
     * Note: 设置参数过滤检查
     * Date: 2023-07-12
     * Time: 13:59
     * @param string $filter 参数过滤
     * @return $this
     */
    public function filter(string $filter)
    {
        $this->option['filter'] = $filter;

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
     * Note: 设置需要合并的路由参数
     * Date: 2023-08-01
     * Time: 18:27
     * @param array $option 路由参数
     * @return $this
     */
    public function mergeOptions(array $option = [])
    {
        $this->mergeOptions = array_merge($this->mergeOptions, $option);

        return $this;
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

        if ($this->parent) {
            $parentOption = $this->parent->getOption();

            foreach ($this->mergeOptions as $item) {
                if (isset($parentOption[$item]) && $option[$item]) {
                    $option[$item] = array_merge($parentOption[$item], $option[$item]);
                }
            }

            $option = array_merge($parentOption, $option);
        }

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

        if ($this->parent) {
            $pattern = array_merge($this->parent->getPattern(), $pattern);
        }

        if ($name == '') {
            return $pattern;
        }

        return $pattern[$name] ?? null;
    }


    /**
     * Note: 解析URL的pathinfo路径
     * Date: 2022-10-09
     * Time: 16:47
     * @param string $url URL地址
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
     * Note: 允许跨域
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
     * Note: 表单令牌验证
     * Date: 2023-08-02
     * Time: 14:33
     * @param string $token 表单令牌token名称
     * @return $this
     */
    public function token(string $token = '__token__')
    {
        return $this->middleware(FormTokenCheck::class, $token);
    }

    /**
     * Note: 设置路由缓存
     * Date: 2023-07-12
     * Time: 18:22
     * @param array|string $cache 缓存
     * @return $this
     */
    public function cache($cache)
    {
        return $this->middleware(CheckRequestCache::class, $cache);
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

        //伪静态后缀检查
        if ($request->url() != '/' && (isset($option['ext']) && stripos('|' . $option['ext'] . '|', '|' . $request->ext() . '|') === false) || (isset($option['deny_ext']) && stripos('|' . $option['deny_ext'] . '|', '|' . $request->ext() . '|') !== false)) {
            return false;
        }

        //域名检测
        if (isset($option['domain']) && !in_array($option['domain'], [$request->host(true), $request->subDomain()])) {
            return false;
        }

        //HTTPS检测
        if (isset($option['https']) && $option['https'] && !$request->isSsl()) {
            return false;
        }

        //请求参数检测
        if (isset($option['filter'])) {
            foreach ($option['filter'] as $name => $value) {
                if ($request->param($name, '', null) != $value) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Note: 设置路由规则全局有效
     * Date: 2023-07-13
     * Time: 14:26
     * @return $this
     */
    public function crossDomainRule()
    {
        if ($this instanceof RuleGroup) {
            $method = '*';
        } else {
            $method = $this->method;
        }

        $this->router->setCrossDomainRule($this, $method);

        return $this;
    }

    /**
     * Note: 解析匹配到的规则路由
     * Date: 2022-10-29
     * Time: 17:36
     * @param Request $request 请求对象
     * @param string $rule 路由规则
     * @param mixed $route 路由地址
     * @param string $url URL地址
     * @param array $option 路由参数
     * @param array $matches 匹配到的变量
     * @return Dispatch
     */
    public function parseRule(Request $request, string $rule, $route, string $url, array $option = [], array $matches = [])
    {
        if (is_string($route) && isset($option['prefix'])) {
            $route = $option['prefix'] . $route;
        }

        $search = $replace = [];
        $extraParams = true;
        $depr = $this->router->config('pathinfo_depr');
        foreach ($matches as $key => $value) {
            $search[] = '<' . $key . '>';
            $replace[] = $value;

            $search[] = ':' . $key;
            $replace[] = $value;

            if (strpos($value, $depr)) {
                $extraParams = false;
            }
        }

        if (is_string($route)) {
            $route = str_replace($search, $replace, $route);
        }

        if ($extraParams) {
            $count = substr_count($rule, '/');
            $url = array_slice(explode('|', $url), $count + 1);
            $this->parseUrlParams(implode('|', $url), $matches);
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
        } elseif (strpos($route, '@') !== false || strpos($route, '::') !== false || strpos($route, '\\') !== false) {
            $route = str_replace('::', '@', $route);
            $result = $this->dispatchMethod($result, $route);
        } else {
            //路由到控制器/操作
            $result = $this->dispatchController($request, $route);
        }

        return $result;
    }

    /**
     * Note: 解析URL地址为 模块/控制器/操作
     * Date: 2023-07-27
     * Time: 14:27
     * @param Request $request 请求对象
     * @param string $route 路由地址
     * @return CallbackDispatch
     */
    protected function dispatchMethod(Request $request, string $route)
    {
        $path = $this->parseUrlPath($route);

        $route = str_replace('/', '@', implode('/', $path));
        $method = strpos($route, '@') ? explode('@', $route) : $route;

        return new CallbackDispatch($request, $this, $method, $this->vars);
    }

    /**
     * Note: 解析URL地址为 模块/控制器/操作
     * Date: 2022-11-09
     * Time: 18:20
     * @param Request $request 请求对象
     * @param string $route 路由地址
     * @return ControllerDispatch
     */
    protected function dispatchController(Request $request, string $route)
    {
        $path = $this->parseUrlPath($route);

        $action = array_pop($path);
        $controller = !empty($path) ? array_pop($path) : null;

        return new ControllerDispatch($request, $this, [$controller, $action], $this->vars);
    }

    /**
     * Note: 解析URL中的参数
     * Date: 2023-07-18
     * Time: 11:21
     * @param string $url 路由地址
     * @param array $var 变量
     * @return void
     */
    protected function parseUrlParams(string $url, array &$var = [])
    {
        if ($url) {
            preg_replace_callback('/(\w+)\|([^\|]+)/', function ($match) use (&$var) {
                $var[$match[1]] = strip_tags($match[2]);
            }, $url);
        }
    }

    /**
     * 生成路由规则的正则
     * @access protected
     * @param string $rule 路由规则
     * @param array $match 匹配的变量
     * @param array $pattern 路由变量规则
     * @param array $option 路由参数
     * @param bool $completeMatch 路由是否完全匹配
     * @param string $suffix 路由正则变量后缀
     * @return string
     */
    protected function buildRuleRegex(string $rule, array $match, array $pattern = [], array $option = [], bool $completeMatch = false, string $suffix = ''): string
    {
        //$match = ['/<id>']; //变量
        foreach ($match as $name) {
            $value = $this->buildNameRegex($name, $pattern, $suffix);
            if ($value) {
                $origin[] = $name;
                $replace[] = $value;
            }
        }

        // 是否区分 / 地址访问
        if ($rule != '/') {
            if (!empty($option['remove_slash'])) {
                $rule = rtrim($rule, '/');
            } elseif (substr($rule, -1) == '/') {
                $rule = rtrim($rule, '/');
                $hasSlash = true;
            }
        }

        $regex = isset($replace) ? str_replace($origin, $replace, $rule) : $rule;
        $regex = str_replace([')?/', ')?-'], [')/', ')-'], $regex);

        if (isset($hasSlash)) {
            $regex .= '/';
        }

        return $regex . ($completeMatch ? '$' : '');
    }

    /**
     * 生成路由变量的正则
     * @access protected
     * @param string $name 路由变量
     * @param array $pattern 变量规则
     * @param string $suffix 路由正则变量后缀
     * @return string
     */
    protected function buildNameRegex(string $name, array $pattern, string $suffix): string
    {
        $optional = '';
        $slash = substr($name, 0, 1);

        if (in_array($slash, ['/', '-'])) {
            $prefix = $slash;
            $name = substr($name, 1);
            $slash = substr($name, 0, 1);
        } else {
            $prefix = '';
        }

        if ('<' != $slash) {
            return '';
        }

        if (strpos($name, '?')) {
            $name = substr($name, 1, -2);
            $optional = '?';
        } elseif (strpos($name, '>')) {
            $name = substr($name, 1, -1);
        }

        if (isset($pattern[$name])) {
            $nameRule = $pattern[$name];
            if (strpos($nameRule, '/') === 0 && substr($nameRule, -1) == '/') {
                $nameRule = substr($nameRule, 1, -1);
            }
        } else {
            $nameRule = $this->router->config('default_route_pattern');
        }

        return '(' . $prefix . '(?<' . $name . $suffix . '>' . $nameRule . '))' . $optional;
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