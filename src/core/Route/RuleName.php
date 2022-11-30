<?php
declare(strict_types=1);

namespace Enna\Framework\Route;


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
     * 路由分组
     * @var array
     */
    protected $group = [];

    /**
     * Note: 注册路由标识
     * Date: 2022-10-22
     * Time: 16:04
     * @param string $name
     * @param RuleItem $ruleItem
     * @param bool $first
     * @return void
     */
    public function setName(string $name, RuleItem $ruleItem, bool $first)
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
     * Note: 注册路由规则
     * Date: 2022-10-22
     * Time: 16:20
     * @param string $rule
     * @param RuleItem $ruleItem
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

    /**
     * Note: 注册路由分组标识
     * Date: 2022-10-28
     * Time: 10:32
     * @param string $name 路由分组标识
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
}