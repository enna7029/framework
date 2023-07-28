<?php
declare(strict_types=1);

namespace Enna\Framework\Route;

use Closure;
use Enna\Framework\Request;
use Enna\Framework\Route;

/**
 * 路由规则类
 * Class RuleItem
 * @package Enna\Framework\Route
 */
class RuleItem extends Rule
{
    /**
     * 是否设置MISS路由
     * @var bool
     */
    protected $miss = false;

    /**
     * 是否为OPTIONS注册规则
     * @var bool
     */
    protected $autoOption = false;

    /**
     * RuleItem constructor.
     * @param Route $router 路由实例
     * @param RuleGroup $parent 上级对象
     * @param string $name 路由标识
     * @param string $rule 路由规则
     * @param string|Closure $route 路由地址
     * @param string $method 请求类型
     */
    public function __construct(Route $router, RuleGroup $parent, string $name = null, string $rule = '', $route = null, string $method = '*')
    {
        $this->router = $router;
        $this->parent = $parent;
        $this->name = $name;
        $this->route = $route;
        $this->method = $method;

        //路由规则预处理
        $this->setRule($rule);

        //设置路由标识
        $this->setRuleName();

        //设置路由规则
        $this->router->setRule($this->rule, $this);
    }

    /**
     * Note: 路由规则预处理
     * Date: 2022-10-22
     * Time: 11:58
     * @param string $rule 路由规则
     * @return void
     */
    public function setRule(string $rule)
    {
        //是否完整匹配
        if (substr($rule, -1, 1) == '$') {
            $rule = substr($rule, 0, -1);
            $this->option['complete_match'] = true;
        }

        $rule = '/' != $rule ? ltrim($rule, '/') : '';

        //如果有父级对象并且父级对象有名称,则增加路由规则前缀
        if ($this->parent && $prefix = $this->parent->getFullName()) {
            $rule = $prefix . ($rule ? '/' . ltrim($rule, '/') : '');
        }

        if (strpos($rule, ':') !== false) {
            $this->rule = preg_replace(['/\[\:(\w+)\]/', '/\:(\w+)/'], ['<\1?>', '<\1>'], $rule);
        } else {
            $this->rule = $rule;
        }
    }

    /**
     * Note: 设置路由标识
     * Date: 2023-07-19
     * Time: 14:07
     * @param string $name
     * @return $this
     */
    public function name(string $name)
    {
        $this->name = $name;
        $this->setRuleName(true);

        return $this;
    }

    /**
     * Note: 设置路由标识
     * Date: 2022-10-22
     * Time: 15:57
     * @param bool $first 是否插入开头
     * @return void
     */
    protected function setRuleName(bool $first = false)
    {
        if ($this->name) {
            $this->router->setName($this->name, $this, $first);
        }
    }

    /**
     * Note: 设置当前路由为自动注册OPTIONS
     * Date: 2022-10-22
     * Time: 15:07
     * @return $this
     */
    public function setAutoOptions()
    {
        $this->autoOption = true;

        return $this;
    }

    /**
     * Note: 判断当前路由规则是否为自动注册的OPTIONS路由
     * Date: 2023-07-27
     * Time: 17:43
     * @return bool
     */
    public function isAutoOptions()
    {
        return $this->autoOption;
    }

    /**
     * Note: 获取当前路由URL的后缀
     * Date: 2022-10-22
     * Time: 17:35
     * @return mixed|null
     */
    public function getSuffix()
    {
        if (isset($this->option['ext'])) {
            $suffix = $this->option['ext'];
        } else {
            $suffix = null;
        }

        return $suffix;
    }

    /**
     * Note: 设置MISS路由
     * Date: 2022-10-27
     * Time: 10:26
     * @return $this
     */
    public function setMiss()
    {
        $this->miss = true;

        return $this;
    }

    /**
     * Note: 判断路由规则是否为MISS路由
     * Date: 2022-10-27
     * Time: 10:28
     * @return bool
     */
    public function isMiss()
    {
        return $this->miss;
    }

    /**
     * Note: 检测路由
     * Date: 2022-10-29
     * Time: 14:57
     * @param Request $request 请求对象
     * @param string $url 地址
     * @param bool $completeMatch 是否完全匹配
     * @return Dispatch|false
     */
    public function check(Request $request, string $url, bool $completeMatch = false)
    {
        return $this->checkRule($request, $url, null, $completeMatch);
    }

    /**
     * Note: 检测路由
     * Date: 2022-10-29
     * Time: 14:59
     * @param Request $request
     * @param string $url 地址
     * @param null $match 匹配变量
     * @param bool $completeMatch 路由是否完全匹配
     * @return Dispatch|false
     */
    public function checkRule(Request $request, string $url, $match = null, bool $completeMatch = false)
    {
        if (!$this->checkOption($this->option, $request)) {
            return false;
        }

        $option = $this->getOption();
        $pattern = $this->getPattern();
        $url = $this->urlSuffixCheck($request, $url, $option);

        //检查匹配的变量
        if (is_null($match)) {
            $match = $this->match($url, $option, $pattern, $completeMatch);
        }

        if ($match !== false) {
            return $this->parseRule($request, $this->rule, $this->route, $url, $option, $match);
        }

        return false;
    }

    /**
     * Note: URL后缀检查
     * Date: 2023-07-26
     * Time: 10:51
     * @param Request $request 请求对象
     * @param string $url 访问地址
     * @param array $option 路由参数
     * @return string
     */
    protected function urlSuffixCheck(Request $request, string $url, array $option = [])
    {
        if (!empty($option['remove_slash']) && $this->rule != '/') {
            $this->rule = rtrim($this->rule, '/');
            $url = rtrim($url, '|');
        }

        if (isset($option['ext'])) {
            $url = preg_replace('/\.(' . $request->ext() . ')$/i', '', $url);
        }

        return $url;
    }

    /**
     * Note: 检测URL和规则是否匹配,并返回匹配的变量
     * Date: 2022-10-29
     * Time: 15:45
     * @param string $url URL地址
     * @param array $option 选项
     * @param array $pattern 变量规则
     * @param bool $completeMatch 是否完全匹配
     * @return array|false
     */
    private function match(string $url, array $option, array $pattern, bool $completeMatch = false)
    {
        if (isset($option['complete_match'])) {
            $completeMatch = $option['complete_match'];
        }

        $depr = $this->router->config('pathinfo_depr');

        $var = [];
        $url = $depr . str_replace('|', $depr, $url);
        $rule = $depr . str_replace('/', $depr, $this->rule);

        //对首页/的特殊处理
        if ($rule == $depr && $depr != $url) {
            return false;
        }

        //对没有变量的路由,将路由规则与路由地址进行匹配,并返回变量空数组
        if (strpos($this->rule, '<') === false) {
            if (strcasecmp($rule, $url) === 0 || (!$completeMatch && strncasecmp($rule . $depr, $url . $depr, strlen($rule . $depr)) === 0)) {
                return $var;
            }
            return false;
        }

        //将含有变量的规则查分,对比路由规则和路由地址
        $slash = preg_quote('/-' . $depr, '/');
        if ($matchRule = preg_split('/[' . $slash . ']?<\w+\??>/', $rule, 2)) {
            if ($matchRule[0] && strncasecmp($rule, $url, strlen($matchRule[0])) !== 0) {
                return false;
            }
        }

        if (preg_match_all('/[' . $slash . ']?<?\w+\??>?/', $rule, $matches)) {
            $regex = $this->buildRuleRegex($rule, $matches[0], $pattern, $option, $completeMatch);
            try {
                if (!preg_match('~^' . $regex . '~u', $url, $match)) {
                    return false;
                }
            } catch (\Exception $e) {
                throw new Exception('route pattern error');
            }

            foreach ($match as $key => $val) {
                if (is_string($key)) {
                    $var[$key] = $val;
                }
            }
        }

        return $var;
    }
}