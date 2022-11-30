<?php
declare(strict_types=1);

namespace Enna\Framework\Route;

use Enna\Framework\Container;
use Enna\Framework\Request;
use Enna\Framework\Route;
use Mockery\Matcher\Closure;

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

    public function __construct(Route $router, RuleGroup $parent = null, string $name = '', $rule = '')
    {
        $this->router = $router;
        $this->parent = $parent;
        $this->name = trim($name, '/');
        $this->rule = $rule;

        $this->setFullName();

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
        if ($this->parent && $this->parent->getFullName()) {
            $this->fullName = $this->parent->getFullName() . ($this->name ? '/' . $this->name : '');
        } else {
            $this->fullName = $this->name;
        }

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

        //注册路由规则
        $this->addRuleItem($ruleItem, $method);

        return $ruleItem;
    }

    /**
     * Note: 添加分组下的路由规则
     * Date: 2022-10-22
     * Time: 14:58
     * @param Rule $rule 路由规则
     * @param string $method 请求类型
     * @return $this
     */
    public function addRuleItem(Rule $rule, string $method = '*')
    {
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
        $origin = $this->router->getGroup();
        $this->router->setGroup($this);

        if ($rule instanceof \Closure) {
            Container::getInstance()->invokeFunction($rule);
        } elseif (is_string($rule) && $rule) {
            $this->router->bind($rule, $this->domain);
        }

        $this->router->setGroup($origin);
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
    public function check(Request $request, string $url, bool $completeMatch)
    {
        //检查选项有效性
        if (!$this->checkOption($this->option, $request)) {
            return false;
        }

        if ($this instanceof Resource) {
            $this->buildResourceRule();
        } else {
            $this->parseGroupRule($this->rule);
        }

        $method = strtolower($request->method());
        $rules = $this->getRules($method);
        $option = $this->getOption();

        foreach ($rules as $key => $item) {
            $result = $item[1]->check($request, $url, $completeMatch);

            if ($result !== false) {
                return $result;
            }
        }

        if ($this->miss && in_array($this->miss->getMethod(), ['*', $method])) { //未匹配路由规则
            $result = $this->parseRule($request, '', $this->miss->getRoute(), $url, $this->miss->getOption());
        } else {
            $result = false;
        }

        return $result;
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

}
