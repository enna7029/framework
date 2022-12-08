<?php
declare(strict_types=1);

namespace Enna\Framework\Route;

use Enna\Framework\Request;
use Enna\Framework\Route;
use SebastianBergmann\CodeCoverage\DeadCodeDetectionNotSupportedException;

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
     * @param string $route 路由地址
     * @param string $method 请求类型
     */
    public function __construct(Route $router, RuleGroup $parent, string $name = null, string $rule = '', string $route = '', string $method = '*')
    {
        $this->router = $router;
        $this->parent = $parent;
        $this->name = $name;
        $this->route = $route;
        $this->method = $method;

        $this->setRule($rule);

        $this->router->setRule($this->rule, $this);
    }

    /**
     * Note: 设置路由规则
     * Date: 2022-10-22
     * Time: 11:58
     * @param string $rule 路由规则
     * @return void
     */
    public function setRule(string $rule)
    {
        if (substr($rule, -1, 1) == '$') {
            $rule = substr($rule, 0, -1);
        }

        $rule = '/' != $rule ? ltrim($rule, '/') : '';

        if ($this->parent && $prefix = $this->parent->getFullName()) {
            $rule = $prefix . ($rule ? '/' . ltrim($rule, '/') : '');
        }

        if (strpos($rule, ':') !== false) {
            $this->rule = preg_replace(['/\[\:(\w+)\]/', '/\:(\w+)/'], ['<\1?>', '<\1>'], $rule);
        } else {
            $this->rule = $rule;
        }

        $this->setRuleName();
    }

    /**
     * Note: 设置路由标识
     * Date: 2022-10-22
     * Time: 15:57
     * @param bool $first 是否插入开头
     * @return void
     */
    protected function setRuleName($first = false)
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
        $completeMatch = true;
        $option = $this->getOption();
        $pattern = $this->getPattern();

        if (is_null($match)) {
            $match = $this->checkMatch($url, $option, $pattern, $completeMatch);
        }

        if ($match !== false) {
            return $this->parseRule($request, $this->rule, $this->route, $url, $option, $match);
        }

        return false;
    }

    /**
     * Note: 检测URL和规则是否匹配
     * Date: 2022-10-29
     * Time: 15:45
     * @param string $url URL地址
     * @param array $option 选项
     * @param array $pattern 变量规则
     * @param bool $completeMatch 是否完全匹配
     * @return array|false
     */
    private function checkMatch(string $url, array $option, array $pattern, bool $completeMatch = false)
    {
        $depr = '/';

        $var = [];
        $url = $depr . $url;
        $rule = $depr . $this->rule;

        if ($rule == $depr && $depr != $url) {
            return false;
        }

        if (strpos($this->rule, '<') === false) {
            if (strcasecmp($rule, $url) === 0 || (!$completeMatch && strncasecmp($rule . '/', $url . '/', strlen($rule . '/')) === 0)) {
                return $var;
            }
        }

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

    /**
     * 生成路由的正则规则
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
        //$match = ['/<id>'];
        foreach ($match as $name) {
            $value = $this->buildNameRegex($name, $pattern, $suffix);
            if ($value) {
                $origin[] = $name;
                $replace[] = $value;
            }
        }

        // 是否区分 / 地址访问
        if ('/' != $rule) {
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
     * 生成路由变量的正则规则
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
            if (0 === strpos($nameRule, '/') && '/' == substr($nameRule, -1)) {
                $nameRule = substr($nameRule, 1, -1);
            }
        } else {
            $nameRule = $this->router->config('default_route_pattern');
        }

        return '(' . $prefix . '(?<' . $name . $suffix . '>' . $nameRule . '))' . $optional;
    }

}