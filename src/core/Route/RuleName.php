<?php
declare(strict_types=1);

namespace Enna\Framework\Route;

/**
 * 路由标识管理类
 * Class RuleName
 * @package Enna\Framework\Route
 */
class RuleName
{
    /**
     * 路由标识
     * @var array
     */
    protected $item = [];

    /**
     * 路由规则
     * @var array
     */
    protected $rule = [];

    /**
     * 分组路由标识
     * @var array
     */
    protected $group = [];

    /**
     * Note: 注册路由标识
     * Date: 2022-10-22
     * Time: 16:04
     * @param string $name 路由标识
     * @param RuleItem $ruleItem 路由规则
     * @param bool $first 是否优先
     * @return void
     */
    public function setName(string $name, RuleItem $ruleItem, bool $first = false)
    {
        $name = strtolower($name);
        $item = $this->getRuleItemInfo($ruleItem);
        if ($first && isset($this->item[$name])) {
            array_unshift($this->item[$name], $item);
        } else {
            $this->item[$name][] = $item;
        }
    }

    /**
     * Note: 根据路由标识获取路由信息(用于URL生成)
     * Date: 2023-07-28
     * Time: 17:50
     * @param string $name 路由标识
     * @param string $domain 域名
     * @param string $method 请求类型
     * @return array
     */
    public function getName(string $name = null, string $domain = null, string $method = '*')
    {
        if (is_null($name)) {
            return $this->item;
        }

        $name = strtolower($name);
        $method = strtolower($method);

        $result = [];
        if (isset($this->item[$name])) {
            if (is_null($domain)) {
                $result = $this->item[$name];
            } else {
                foreach ($this->item[$name] as $item) {
                    $itemDomain = $item['domain'];
                    $itemMethod = $item['method'];

                    if (($itemDomain == $domain || $itemDomain == '-') && ($itemMethod == '*' || $method == '*' || $method == $itemMethod)) {
                        $result[] = $item;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Note: 注册路由规则
     * Date: 2022-10-22
     * Time: 16:20
     * @param string $rule 路由规则
     * @param RuleItem $ruleItem 路由实例对象
     * @return void
     */
    public function setRule(string $rule, RuleItem $ruleItem)
    {
        $route = $ruleItem->getRoute();

        if (is_string($route)) {
            $this->rule[$rule][$route] = $ruleItem;
        } else {
            $this->rule[$rule][] = $ruleItem;
        }
    }

    /**
     * Note: 根据路由规则获取路由实例对象(列表)
     * Date: 2023-07-28
     * Time: 17:49
     * @param string $rule 路由规则
     * @return RuleItem[]
     */
    public function getRule(string $rule)
    {
        return $this->rule[$rule] ?? [];
    }

    /**
     * Note: 注册分组路由标识
     * Date: 2022-10-28
     * Time: 10:32
     * @param string $name 分组路由标识
     * @param RuleGroup $ruleGroup 路由分组
     * @return void
     */
    public function setGroup(string $name, RuleGroup $ruleGroup)
    {
        $this->group[strtolower($name)] = $ruleGroup;
    }

    /**
     * Note: 根据路由分组标识获取路由分组
     * Date: 2022-10-28
     * Time: 16:05
     * @param string $name 路由分组标识
     * @return RuleGroup|null
     */
    public function getGroup(string $name)
    {
        return $this->group[strtolower($name)] ?? null;
    }

    /**
     * Note: 导入路由标识
     * Date: 2023-07-17
     * Time: 16:00
     * @param array $item 路由标识
     * @return void
     */
    public function import(array $item)
    {
        $this->item = $item;
    }

    /**
     * Note: 获取全部路由列表
     * Date: 2023-07-28
     * Time: 18:09
     * @return array
     */
    public function getRuleList()
    {
        $list = [];
        foreach ($this->rule as $rule => $rules) {
            foreach ($rules as $item) {
                $val = [];

                foreach (['method', 'rule', 'name', 'route', 'domain', 'pattern', 'option'] as $param) {
                    $call = 'get' . $param;
                    $val[$param] = $item->$call();
                }

                if ($item->isMiss()) {
                    $val['rule'] .= '<MISS>';
                }

                $list[] = $val;
            }
        }

        return $list;
    }

    /**
     * Note: 清空路由规则
     * Date: 2023-07-28
     * Time: 18:02
     * @return void
     */
    public function clear()
    {
        $this->item = [];
        $this->rule = [];
        $this->group = [];
    }

    /**
     * Note: 获取路由信息
     * Date: 2022-10-22
     * Time: 16:13
     * @param RuleItem $ruleItem 路由规则
     * @return array
     */
    protected function getRuleItemInfo(RuleItem $ruleItem)
    {
        return [
            'rule' => $ruleItem->getRule(),
            'domain' => $ruleItem->getDomain(),
            'method' => $ruleItem->getMethod(),
            'suffix' => $ruleItem->getSuffix(),
        ];
    }
}