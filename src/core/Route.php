<?php
declare(strict_types=1);

namespace Enna\Framework;

use Closure;
use Enna\Framework\Route\Domain;
use Enna\Framework\Route\Dispatch\Url as UrlDispatch;
use Enna\Framework\Route\Dispatch;
use Enna\Framework\Route\Dispatch\Callback;
use Enna\Framework\Route\RuleGroup;
use Enna\Framework\Route\RuleItem;
use Enna\Framework\Route\RuleName;
use Enna\Framework\Route\Resource;
use Enna\Framework\Route\Url;
use Enna\Framework\Exception\RouteNotFoundException;
use Enna\Framework\Route\Rule;

/**
 * 路由管理类
 * Class Route
 * @package Enna\Framework
 */
class Route
{
    /**
     * REST定义
     * @var array
     */
    protected $rest = [
        'index' => ['get', '', 'index'],
        'create' => ['get', '/create', 'create'],
        'edit' => ['get', '/<id>/edit', 'edit'],
        'read' => ['get', '/<id>', 'read'],
        'save' => ['post', '', 'save'],
        'update' => ['put', '/<id>', 'update'],
        'delete' => ['delete', '/<id>', 'delete'],
    ];

    /**
     * 配置参数
     * @var array
     */
    protected $config = [
        'url_route_must' => false,
        //url伪静态后缀
        'url_html_suffix' => 'html',
        //默认的路由变量规则
        'default_route_pattern' => '[\w\.]+',
        //控制器层名称
        'controller_layer' => 'controller',
        //空控制器名
        'empty_contrller' => 'Error',
        //是否使用控制器控制
        'controller_suffix' => false,
        //默认控制器名
        'defualt_controller' => 'Index',
        //默认操作名
        'default_action' => 'index'
    ];

    /**
     * 应用实例
     * @var App
     */
    protected $app;

    /**
     * 请求对象
     * @var Request
     */
    protected $request;

    /**
     * 当前分组对象
     * @var RuleGroup
     */
    protected $group;

    /**
     * 域名对象
     * @var Domain[]
     */
    protected $domains = [];

    /**
     * 路由标识
     * @var RuleName
     */
    protected $ruleName;

    /**
     * 当前host
     * @var string
     */
    protected $host;

    /**
     * 路由是否延迟加载
     * @var bool
     */
    protected $lazy = false;

    /**
     * 路由绑定
     * @var array
     */
    protected $bind = [];

    /**
     * 跨域路由规则
     * @var RuleGroup
     */
    protected $cross;

    /**
     * 是否去除URL最后的斜线
     * @var bool
     */
    protected $removeSlash = false;

    /**
     * 分组路由规则是否合并解析
     * @var bool
     */
    protected $mergeRuleRegex = false;

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->ruleName = new RuleName();
        $this->setDefaultDomain();

        //读取路由缓存解析
        if (is_file($this->app->getRuntimePath() . 'route.php')) {
            $this->import(include $this->app->getRuntimePath() . 'route.php');
        }

        $this->config = array_merge($this->config, $this->app->config->get('route'));
    }

    /**
     * Note: 设置默认域名
     * Date: 2022-09-29
     * Time: 18:19
     * @return void
     */
    protected function setDefaultDomain(): void
    {
        $domain = new Domain($this);

        $this->domains['-'] = $domain;

        $this->group = $domain;
    }

    /**
     * Note: 路由初始化
     * Date: 2023-07-13
     * Time: 17:57
     * @return void
     */
    protected function init()
    {
        if (!empty($this->config['middleware'])) {
            $this->app->middleware->import($this->config['middleware'], 'route');
        }

        $this->lazy($this->config['url_lazy_route']);
        $this->mergeRuleRegex = $this->config['route_rule_merge'];
        $this->removeSlash = $this->config['remove_slash'];

        $this->group->removeSlash($this->removeSlash);
    }

    /**
     * Note: 设置分组路由或域名路由是否合并解析
     * Date: 2023-07-13
     * Time: 10:55
     * @param bool $merge
     * @return $this
     */
    public function mergeRuleRegex(bool $merge = true)
    {
        $this->mergeRuleRegex = $merge;
        $this->group->mergeRuleRegex($merge);

        return $this;
    }

    /**
     * Note: 注册路由规则
     * Date: 2022-10-22
     * Time: 10:58
     * @param string $rule 路由规则
     * @param mixed $route 路由地址
     * @param string $method 请求类型
     * @return RuleItem
     */
    public function rule(string $rule, $route, $method = '*')
    {
        return $this->group->addRule($rule, $route, $method);
    }

    /**
     * Note: 注册路由
     * Date: 2022-10-22
     * Time: 10:56
     * @param string $rule 路由规则
     * @param mixed $route 路由地址
     * @return RuleItem
     */
    public function any(string $rule, $route)
    {
        return $this->rule($rule, $route, '*');
    }

    /**
     * Note: 注册GET路由
     * Date: 2022-10-22
     * Time: 10:56
     * @param string $rule 路由规则
     * @param mixed $route 路由地址
     * @return RuleItem
     */
    public function get(string $rule, $route)
    {
        return $this->rule($rule, $route, 'GET');
    }

    /**
     * Note: 注册POST路由
     * Date: 2022-10-25
     * Time: 18:00
     * @param string $rule 路由规则
     * @param mixed $route 路由地址
     * @return RuleItem
     */
    public function post(string $rule, $route)
    {
        return $this->rule($rule, $route, 'POST');
    }

    /**
     * Note: 注册PUT路由
     * Date: 2022-10-25
     * Time: 18:02
     * @param string $rule 路由规则
     * @param mixed $route 路由地址
     * @return RuleItem
     */
    public function put(string $rule, $route)
    {
        return $this->rule($rule, $route, 'PUT');
    }

    /**
     * Note: 注册DELETE路由
     * Date: 2022-10-25
     * Time: 18:02
     * @param string $rule 路由规则
     * @param mixed $route 路由地址
     * @return RuleItem
     */
    public function delete(string $rule, $route)
    {
        return $this->rule($rule, $route, 'DELETE');
    }

    /**
     * Note: 注册PATCH路由
     * Date: 2022-10-25
     * Time: 18:02
     * @param string $rule 路由规则
     * @param mixed $route 路由地址
     * @return RuleItem
     */
    public function patch(string $rule, $route)
    {
        return $this->rule($rule, $route, 'PATCH');
    }

    /**
     * Note: 注册HEAD路由
     * Date: 2022-10-25
     * Time: 18:02
     * @param string $rule 路由规则
     * @param mixed $route 路由地址
     * @return RuleItem
     */
    public function head(string $rule, $route)
    {
        return $this->rule($rule, $route, 'HEAD');
    }

    /**
     * Note: 注册OPTIONS路由
     * Date: 2022-10-25
     * Time: 18:02
     * @param string $rule 路由规则
     * @param mixed $route 路由地址
     * @return RuleItem
     */
    public function options(string $rule, $route)
    {
        return $this->rule($rule, $route, 'OPTIONS');
    }

    /**
     * Note: 注册资源路由
     * Date: 2022-10-25
     * Time: 18:28
     * @param string $rule 路由规则
     * @param string $route 路由地址
     * @return Resource
     */
    public function resource(string $rule, string $route)
    {
        return (new Resource($this, $this->group, $rule, $route, $this->rest))
            ->lazy($this->lazy);
    }

    /**
     * Note: 注册重定向路由
     * Date: 2022-10-26
     * Time: 10:37
     * @param string $rule 路由规则
     * @param string $route 路由地址
     * @param int $status 状态码
     * @return RuleItem
     */
    public function redirect(string $rule, string $route = '', int $status = 301)
    {
        return $this->rule($rule, function (Request $request) use ($route, $status) {
            $search = $replace = [];
            $matches = $request->rule()->getVars();

            foreach ($matches as $key => $value) {
                $search[] = '<' . $key . '>';
                $replace[] = $value;

                $search[] = ':' . $key;
                $replace[] = $value;
            }
            $route = str_replace($search, $replace, $route);

            return Response::create($route, 'redirect')->code($status);
        }, '*');
    }

    /**
     * Note: 注册路由分组
     * Date: 2022-10-26
     * Time: 15:52
     * @param string|Closure $name 分组名称或者闭包
     * @param mixed $route 分组路由
     * @return RuleGroup
     */
    public function group($name, $route = null)
    {
        if ($name instanceof Closure) {
            $route = $name;
            $name = '';
        }

        return (new RuleGroup($this, $this->group, $name, $route))
            ->lazy($this->lazy)
            ->removeSlash($this->removeSlash)
            ->mergeRuleRegex($this->mergeRuleRegex);
    }

    /**
     * Note: 注册域名路由
     * Date: 2022-10-26
     * Time: 18:39
     * @param string|array $name 域名
     * @param mixed $rule 路由规则
     * @return Domain
     */
    public function domain($name, $rule = null)
    {
        $domainName = is_array($name) ? array_shift($name) : $name;

        if (!isset($this->domains[$domainName])) {
            $domin = (new Domain($this, $domainName, $rule))
                ->lazy($this->lazy)
                ->removeSlash($this->removeSlash)
                ->mergeRuleRegex($this->mergeRuleRegex);

            $this->domains[$domainName] = $domin;
        } else {
            $domain = $this->domains[$domainName];
            $domain->parseGroupRule($rule);
        }

        if (is_array($name) && !empty($name)) {
            foreach ($name as $item) {
                $this->domains[$item] = $domainName;
            }
        }

        return $domin;
    }

    /**
     * Note: 设置路由绑定
     * Date: 2022-10-26
     * Time: 18:27
     * @param string $bind 绑定信息
     * @param string $domain 域名
     * @return $this
     */
    public function bind(string $bind, string $domain = null)
    {
        $domain = is_null($domain) ? '-' : $domain;

        $this->bind[$domain] = $bind;

        return $this;
    }

    /**
     * Note: 读取路由绑定信息
     * Date: 2023-07-17
     * Time: 18:28
     * @return array
     */
    public function getBind()
    {
        return $this->bind;
    }

    /**
     * Note: 读取路由绑定
     * Date: 2023-07-17
     * Time: 17:57
     * @param string $domain 域名
     * @return string|null
     */
    public function getDomainBind(string $domain = null)
    {
        if (is_null($domain)) {
            $domain = $this->host;
        } elseif (strpos($domain, '.') === false && $this->request) {
            $domain .= '.' . $this->request->rootDomain();
        }

        if ($this->request) {
            $subDomain = $this->request->subDomain();

            if (strpos($subDomain, '.')) {
                $name = '*' . strstr($subDomain, '.');
            }
        }

        if (isset($this->bind[$domain])) {
            $result = $this->bind[$domain];
        } elseif (isset($name) && isset($this->bind[$name])) {
            $result = $this->bind[$name];
        } elseif (!empty($subDomain) && isset($this->bind['*'])) {
            $result = $this->bind['*'];
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Note: 注册MISS路由
     * Date: 2022-10-27
     * Time: 10:15
     * @param mixed $route 路由地址
     * @param string $method 请求类型
     * @return RuleItem
     */
    public function miss($route, string $method = '*')
    {
        return $this->group->miss($route, $method);
    }

    /**
     * Note: 获取路由配置
     * Date: 2022-11-10
     * Time: 15:26
     * @param string|null $name
     * @return mixed
     */
    public function config(string $name = null)
    {
        if (is_null($name)) {
            return $this->config;
        }

        return $this->config[$name] ?? null;
    }

    /**
     * Note: 注册路由标识
     * Date: 2022-10-22
     * Time: 16:00
     * @param string $name 路由标识
     * @param RuleItem $ruleItem 路由规则
     * @param bool $first 是否开头插入
     * @return void
     */
    public function setName(string $name, RuleItem $ruleItem, bool $first = false)
    {
        $this->ruleName->setName($name, $ruleItem, $first);
    }

    /**
     * Note: 注册路由规则
     * Date: 2022-10-22
     * Time: 16:18
     * @param string $rule 路由规则
     * @param RuleItem $ruleItem RuleItem对象
     * @return void
     */
    public function setRule(string $rule, RuleItem $ruleItem)
    {
        $this->ruleName->setRule($rule, $ruleItem);
    }

    /**
     * Note: 读取路由
     * Date: 2023-07-27
     * Time: 17:59
     * @param string $rule 路由规则
     * @return RuleItem[]
     */
    public function getRule(string $rule)
    {
        return $this->ruleName->getRule($rule);
    }

    /**
     * Note: 设置变量规则
     * Date: 2022-10-25
     * Time: 18:27
     * @param array $pattern 规则
     * @return $this
     */
    public function pattern(array $pattern)
    {
        $this->group->pattern($pattern);

        return $this;
    }

    /**
     * Note: 注册路由参数
     * Date: 2023-08-02
     * Time: 14:23
     * @param array $option 参数
     * @return $this
     */
    public function option(array $option)
    {
        $this->group->option($option);

        return $this;
    }

    /**
     * Note: 获取RuleName对象
     * Date: 2022-10-28
     * Time: 10:20
     * @return RuleName
     */
    public function getRuleName()
    {
        return $this->ruleName;
    }

    /**
     * Note: 设置当前分组
     * Date: 2022-10-28
     * Time: 16:28
     * @param RuleGroup $ruleGroup 分组实例
     * @return void
     */
    public function setGroup(RuleGroup $ruleGroup)
    {
        $this->group = $ruleGroup;
    }

    /**
     * Note: 获取指定标识的路由分组 不指定则获取当前分组
     * Date: 2022-10-28
     * Time: 16:24
     * @param string $name 分组标识符
     * @return RuleGroup
     */
    public function getGroup(string $name = '')
    {
        return $name ? $this->ruleName->getGroup($name) : $this->group;
    }

    /**
     * Note: rest方法定义和修改
     * Date: 2023-07-13
     * Time: 11:36
     * @param string|array $name 方法名称
     * @param array|bool $resource 资源
     * @return $this
     */
    public function rest($name, $resource = [])
    {
        if (is_array($name)) {
            $this->rest = $resource ? $name : array_merge($this->rest, $name);
        } else {
            $this->rest[$name] = $resource;
        }

        return $this;
    }

    /**
     * Note: 获取rest方法定义的参数
     * Date: 2023-07-19
     * Time: 17:12
     * @param string|null $name
     * @return array|mixed|string[]|\string[][]|null
     */
    public function getRest(string $name = null)
    {
        if (is_null($name)) {
            return $this->rest;
        }

        return $this->rest[$name] ?? null;
    }

    /**
     * Note: 设置跨域有效路由规则
     * Date: 2023-07-13
     * Time: 14:29
     * @param Rule $rule 路由规则
     * @param string $method 请求类型
     * @return $this
     */
    public function setCrossDomainRule(Rule $rule, string $method = '*')
    {
        if (!isset($this->cross)) {
            $this->cross = (new RuleGroup($this))->mergeRuleRegex($this->mergeRuleRegex);
        }

        $this->cross->addRuleItem($rule, $method);

        return $this;
    }

    /**
     * Note: 设置域名及分组路由(资源路由)是否延迟解析
     * Date: 2023-07-17
     * Time: 15:35
     * @param bool $lazy 路由是否延迟解析
     * @return $this
     */
    public function lazy(bool $lazy = true)
    {
        $this->lazy = $lazy;

        return $this;
    }

    /**
     * Note: 批量导入路由标识
     * Date: 2023-07-17
     * Time: 15:58
     * @param array $name 路由标识
     * @return void
     */
    public function import(array $name)
    {
        $this->ruleName->import($name);
    }

    /**
     * Note: URL生成 支持路由反射
     * Date: 2022-10-27
     * Time: 10:58
     * @param string $url 路由地址
     * @param array $vars 参数
     * @return Url
     */
    public function buildUrl(string $url = '', array $vars = [])
    {
        return $this->app->make(Url::class, [$this, $this->app, $url, $vars], true);
    }

    /**
     * Note: 路由调度
     * Date: 2022-09-28
     * Time: 18:25
     * @param Request $request 请求实例
     * @param Closure|bool $withRoute 路由配置文件的闭包
     * @return Response
     */
    public function dispatch(Request $request, $withRoute = true)
    {
        $this->request = $request;
        $this->host = $this->request->host(true);
        $this->init();

        if ($withRoute) {
            if ($withRoute instanceof Closure) {
                $withRoute();
            }
            $dispatch = $this->check();
        } else {
            $dispatch = $this->url($this->path());
        }

        $dispatch->init($this->app);

        return $this->app->middleware->pipeline('route')
            ->send($request)
            ->then(function () use ($dispatch) {
                return $dispatch->run();
            });

    }

    /**
     * Note: 检测URL路由
     * Date: 2022-09-29
     * Time: 15:03
     * @return Dispatch|false
     * @throws RouteNotFoundException
     */
    public function check()
    {
        $url = str_replace($this->config['pathinfo_depr'], '|', $this->path());

        $completeMatch = $this->config['route_complete_match'];

        $result = $this->checkDomain()->check($this->request, $url, $completeMatch);

        if ($result === false && !empty($this->cross)) {
            $result = $this->cross->check($this->request, $url, $completeMatch);
        }

        if ($result !== false) {
            return $result;
        } elseif ($this->config['url_route_must']) {
            throw new RouteNotFoundException();
        }

        return $this->url($url);
    }

    /**
     * Note: 获取当前URL的pathinfo信息,不包含后缀
     * Date: 2022-09-29
     * Time: 15:47
     * @return string
     */
    public function path()
    {
        $suffix = $this->config['url_html_suffix'];
        $pathinfo = $this->request->pathinfo();

        if ($suffix === false) {
            $path = $pathinfo;
        } elseif ($suffix) {
            $path = preg_replace('/\.(' . ltrim($suffix, '.') . ')$/i', '', $pathinfo);
        } else {
            $path = preg_replace('/\.(' . ltrim($this->request->ext()) . ')$/i', '', $pathinfo);
        }

        return $path;
    }

    /**
     * Note: URL解析
     * Date: 2022-09-29
     * Time: 18:06
     * @param string $url
     * @return Dispatch
     */
    public function url(string $url)
    {
        if ($this->request->method() === 'OPTIONS') {
            return new Callback($this->request, $this->group, function () {
                return Response::create('', 'html', 204)->header(['Allow' => 'GET,POST,PUT,DELETE']);
            });
        }
        return new UrlDispatch($this->request, $this->group, $url);
    }

    /**
     * Note: 检查域名的路由规则
     * Date: 2022-10-27
     * Time: 16:56
     * @return Domain
     */
    public function checkDomain()
    {
        $item = false;

        //定义了域名路由
        if (count($this->domains) > 1) {
            $subDomain = $this->request->subDomain();
            $array_subDomain = $subDomain ? explode(',', $subDomain) : [];
            $domain2 = $array_subDomain ? array_pop($array_subDomain) : '';
            if ($array_subDomain) {
                $domain3 = array_pop($array_subDomain);
            }

            if (isset($this->domains[$this->host])) {
                $item = $this->domains[$this->host];
            } elseif (isset($this->domains[$subDomain])) {
                $item = $this->domains[$subDomain];
            } elseif (isset($this->domains['*']) && !empty($domain2)) {
                if ($domain2 != 'www') {
                    $item = $this->domains['*'];
                    $panDomain = $domain2;
                }
            } elseif (isset($this->domains['*.' . $domain2]) && !empty($domain3)) {
                $item = $this->domains['*.' . $domain2];
                $panDomain = $domain3;
            }

            if (isset($panDomain)) {
                $this->request->setPanDomain($panDomain);
            }
        }

        if ($item === false) {
            $item = $this->domains['-'];
        }
        if (is_string($item)) {
            $item = $this->domains[$item];
        }

        return $item;
    }

    /**
     * Note: 设置全局路由分组参数
     * Date: 2023-07-19
     * Time: 17:02
     * @param $method
     * @param $args
     * @return false|mixed
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->group, $method], $args);
    }
}