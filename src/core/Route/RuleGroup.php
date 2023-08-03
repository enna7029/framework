<?php
declare(strict_types=1);

namespace Enna\Framework\Route;

use Closure;
use Enna\Framework\Container;
use Enna\Framework\Request;
use Enna\Framework\Route;

/**
 * 路由分组类
 * Class RuleGroup
 * @package Enna\Framework\Route
 */
class RuleGroup extends Rule
{
    /**
     * 分组路由
     * @var array
     */
    protected $rules = [];

    /**
     * 分组路由规则
     * @var mixed
     */
    protected $rule;

    /**
     * MISS路由
     * @var RuleItem
     */
    protected $miss;

    /**
     * 完整名称
     * @var string
     */
    protected $fullName;

    /**
     * RuleGroup constructor.
     * @param Route $router 路由对象
     * @param RuleGroup $parent 上级对象:默认domain对象或父group对象
     * @param string $name 分组名称
     * @param mixed $rule 分组路由
     */
    public function __construct(Route $router, RuleGroup $parent = null, string $name = '', $rule = null)
    {
        $this->router = $router;
        $this->parent = $parent;
        $this->name = trim($name, '/');
        $this->rule = $rule;

        //设置完整名称
        $this->setFullName();

        //设置分组标识
        $this->setGroupName();

        if ($this->parent) {
            $this->domain = $this->parent->getDomain();
            $this->parent->addRuleItem($this);
        }
    }

    /**
     * Note: 设置分组路由的标识
     * Date: 2022-10-28
     * Time: 10:25
     * @return void
     */
    protected function setFullName()
    {
        if (strpos($this->name, ':') !== false) {
            $this->name = preg_replace(['/\[\:(\w+)\]/', '/\:(\w+)/'], ['<\1?>', '<\1>'], $this->name);
        }

        if ($this->parent && $this->parent->getFullName()) {
            $this->fullName = $this->parent->getFullName() . ($this->name ? '/' . $this->name : '');
        } else {
            $this->fullName = $this->name;
        }
    }

    /**
     * Note: 设置分组标识
     * Date: 2023-07-31
     * Time: 18:32
     */
    protected function setGroupName()
    {
        if ($this->name) {
            $this->router->getRuleName()->setGroup($this->name, $this);
        }
    }

    /**
     * Note: 获取完整分组Name
     * Date: 2022-11-16
     * Time: 11:03
     * @return string
     */
    public function getFullName()
    {
        return $this->fullName ?? '';
    }

    /**
     * Note: 获取所属域名路由
     * Date: 2022-10-28
     * Time: 14:34
     * @return string|void
     */
    public function getDomain()
    {
        return $this->domain ?: '-';
    }

    /**
     * Note: 添加分组下的路由规则
     * Date: 2022-10-22
     * Time: 11:05
     * @param string $rule 路由规则
     * @param mixed $route 路由地址
     * @param string $method 请求类型
     * @return RuleItem
     */
    public function addRule(string $rule, $route, string $method = '')
    {
        if (is_string($route)) {
            $name = $route;
        } else {
            $name = null;
        }

        $method = strtolower($method);

        if ($rule === '' || $rule === '/') {
            $rule .= '$';
        }

        //创建路由规则实例
        $ruleItem = new RuleItem($this->router, $this, $name, $rule, $route, $method);

        //注册分组下的路由规则
        $this->addRuleItem($ruleItem, $method);

        return $ruleItem;
    }

    /**
     * Note: 注册分组下的路由规则
     * Date: 2022-10-22
     * Time: 14:58
     * @param Rule $rule 路由规则
     * @param string $method 请求类型
     * @return $this
     */
    public function addRuleItem(Rule $rule, string $method = '*')
    {
        if (strpos($method, '|')) {
            $rule->method($method);
            $method = '*';
        }

        $this->rules[] = [$method, $rule];

        if ($rule instanceof RuleItem && $method != 'options') {
            $this->rules[] = ['options', $rule->setAutoOptions()];
        }

        return $this;
    }

    /**
     * Note: 注册MISS路由
     * Date: 2022-10-27
     * Time: 10:16
     * @param string|Closure $route 路由地址
     * @param string $method 请求类型
     * @return RuleItem
     */
    public function miss($route, string $method = '*')
    {
        $ruleItem = new RuleItem($this->router, $this, null, '', $route, strtolower($method));

        $ruleItem->setMiss();
        $this->miss = $ruleItem;

        return $ruleItem;
    }

    /**
     * Note: 获取分组的MISS路由
     * Date: 2023-08-01
     * Time: 11:06
     * @return RuleItem|null
     */
    public function getMissRule()
    {
        return $this->miss;
    }

    /**
     * Note: 检测分组路由
     * Date: 2022-10-29
     * Time: 10:11
     * @param Request $request 请求对象
     * @param string $url 访问地址
     * @param bool $completeMatch 路由是否完全匹配
     * @return Dispatch|false
     */
    public function check(Request $request, string $url, bool $completeMatch = false)
    {
        //检查选项有效性,检查URL有效性
        if (!$this->checkOption($this->option, $request) || !$this->checkUrl($url)) {
            return false;
        }

        if ($this instanceof Resource) {
            //解析资源路由规则
            $this->buildResourceRule();
        } else {
            //解析分组或域名的路由规则
            $this->parseGroupRule($this->rule);
        }

        //获取当前分组下的路由规则
        $method = strtolower($request->method());
        $rules = $this->getRules($method);

        //获取当前分组下的路由选项
        $option = $this->getOption();

        if (!empty($option['complete_match'])) {
            $completeMatch = $option['complete_match'];
        }

        //合并路由规则,进行路由匹配检查
        if (!empty($option['merge_rule_regex'])) {
            $result = $this->checkMergeRuleRegex($request, $rules, $url, $completeMatch);

            if ($result !== false) {
                return $result;
            }
        }

        //检查分组下的路由
        foreach ($rules as $key => $item) {
            $result = $item[1]->check($request, $url, $completeMatch);

            if ($result !== false) {
                return $result;
            }
        }

        if (!empty($option['dispatcher'])) { //路由分组指定的调度类
            $result = $this->parseRule($request, '', $option['dispatcher'], $url, $option);
        } elseif ($this->miss && in_array($this->miss->getMethod(), ['*', $method])) { //未匹配路由规则
            $result = $this->parseRule($request, '', $this->miss->getRoute(), $url, $this->miss->getOption());
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * Note: 分组URL匹配检查
     * Date: 2023-07-19
     * Time: 17:55
     * @param string $url URL
     * @return bool
     */
    protected function checkUrl(string $url)
    {
        if ($this->fullName) {
            $pos = strpos($this->fullName, '<');

            if ($pos !== false) {
                $str = substr($this->fullName, 0, $pos);
            } else {
                $str = $this->fullName;
            }

            if ($str && stripos(str_replace('|', '/', $url), $str) !== 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Note: 延迟解析分组的路由规则
     * Date: 2022-10-27
     * Time: 18:12
     * @param bool $lazy 是否延迟解析
     * @return $this
     */
    public function lazy(bool $lazy = false)
    {
        if (!$lazy) {
            $this->parseGroupRule($this->rule);
            $this->rule = null;
        }

        return $this;
    }

    /**
     * Note: 解析域名和分组的路由规则
     * Date: 2022-10-27
     * Time: 18:20
     * @param mixed $rule 路由规则
     * @return void
     */
    public function parseGroupRule($rule)
    {
        if (is_string($rule) && is_subclass_of($rule, Dispatch::class)) {
            $this->dispatcher($rule);
            return;
        }

        $origin = $this->router->getGroup();
        $this->router->setGroup($this);

        if ($rule instanceof Closure) {
            Container::getInstance()->invokeFunction($rule);
        } elseif (is_string($rule) && $rule) {
            $this->router->bind($rule, $this->domain);
        }

        $this->router->setGroup($origin);
    }

    /**
     * Note: 获取分组的路由规则
     * Date: 2022-10-29
     * Time: 14:40
     * @param string $method 请求类型
     * @return array
     */
    public function getRules(string $method = '')
    {
        if ($method == '') {
            return $this->rules;
        }

        return array_filter($this->rules, function ($item) use ($method) {
            return $item[0] == $method || $item[0] == '*';
        });
    }

    /**
     * Note: 检测分组路由
     * Date: 2023-08-01
     * Time: 11:14
     * @param Request $request 请求对象
     * @param array $rules 路由规则
     * @param string $url URL地址
     * @param bool $completeMatch 路由是否完全匹配
     * @return Dispatch|false
     */
    protected function checkMergeRuleRegex(Request $request, array &$rules, string $url, bool $completeMatch)
    {
        $depr = $this->router->config('pathinfo_depr');
        $url = $depr . str_replace('|', $depr, $url);

        $regex = [];
        $items = [];
        foreach ($rules as $key => $val) {
            $item = $val[1];
            if ($item instanceof RuleItem) {
                $rule = $depr . str_replace('/', $depr, $item->getRule());

                //对'/'的处理
                if ($rule == $depr && $depr != $url) {
                    unset($rules[$key]);
                    continue;
                }

                //对没有路由变量的处理
                $complete = $this->getOption('complete_match', $completeMatch);
                if (strpos($rule, '<') === false) {
                    if (strcasecmp($rule, $url) === 0 || (!$complete && strncasecmp($rule, $url, strlen($rule)) === 0)) {
                        return $item->checkRule($request, $url, []);
                    }

                    unset($rules[$key]);
                    continue;
                }

                //对含有路由变量的处理
                $slash = preg_quote('/-' . $depr, '/');
                if ($matchRule = preg_split('/[' . $slash . ']?<\w+\??>/', $rule, 2)) {
                    if ($matchRule[0] && strncasecmp($rule, $url, strlen($matchRule[0])) !== 0) {
                        unset($rules[$key]);
                        continue;
                    }
                }
                if (preg_match_all('/[' . $slash . ']?<?\w+\??>?/', $rule, $matches)) {
                    unset($rules[$key]);
                    $option = array_merge($this->getOption(), $item->getOption());
                    $pattern = array_merge($this->getPattern(), $item->getPattern());
                    $regex[$key] = $this->buildRuleRegex($rule, $matches[0], $pattern, $option, $completeMatch, '_ENNA_' . $key);
                    $items[$key] = $item;
                }
            }
        }

        if (empty($regex)) {
            return false;
        }

        try {
            $result = preg_match('~^(?:' . implode('|', $regex) . ')~u', $url, $match);
        } catch (\Exception $e) {
            throw new Exception('route pattern error');
        }
        if ($result) {
            //获取路由变量
            $var = [];
            foreach ($match as $key => $val) {
                if (is_string($key) && $val !== '') {
                    [$name, $pos] = explode('_ENNA_', $key);

                    $var[$name] = $val;
                }
            }

            //获取指定的位置
            if (!isset($pos)) {
                foreach ($regex as $key => $name) {
                    if (strpos(str_replace(['\/', '\-', '\\' . $depr], ['/', '-', $depr], $item), $match[0]) === 0) {
                        $pos = $key;
                        break;
                    }
                }
            }

            //执行RuleItem
            $rule = $items[$pos]->getRule();
            $array = $this->router->getRule($rule);
            foreach ($array as $item) {
                if (in_array($item->getMethod(), ['*', strtolower($request->method())])) {
                    $result = $item->checkRule($request, $url, $var);

                    if ($result !== false) {
                        return $result;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Note: 设置分组的路由前缀
     * Date: 2023-07-13
     * Time: 10:36
     * @param string $prefix 路由前缀
     * @return $this
     */
    public function prefix(string $prefix)
    {
        if ($this->parent && $this->parent->getOption('prefix')) {
            $prefix = $this->parent->getOption('prefix') . $prefix;
        }

        return $this->setOption('prefix', $prefix);
    }

    /**
     * Note: 设置路由分组别名
     * Date: 2023-08-01
     * Time: 11:10
     * @param string $alias 路由分组别名
     * @return $this
     */
    public function alias(string $alias)
    {
        $this->alias = $alias;
        $this->router->getRuleName()->setGroup($alias, $this);

        return $this;
    }

    /**
     * Note: 合并分组的路由规则正则
     * Date: 2023-07-13
     * Time: 10:56
     * @param bool $merge
     * @return RuleGroup
     */
    public function mergeRuleRegex(bool $merge = true)
    {
        return $this->setOption('merge_rule_regex', $merge);
    }

    /**
     * Note: 设置分组的Dispatch调度
     * Date: 2023-07-13
     * Time: 11:02
     * @param string $dispatch 调度类
     * @return $this
     */
    public function dispatcher(string $dispatch)
    {
        return $this->setOption('dispatcher', $dispatch);
    }

    /**
     * Note: 清空分组下的路由规则
     * Date: 2023-08-01
     * Time: 11:02
     * @return void
     */
    public function clear()
    {
        $this->rules = [];
    }
}
