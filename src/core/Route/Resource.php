<?php

namespace Enna\Framework\Route;

use Enna\Framework\Route;

class Resource extends RuleGroup
{
    /**
     * 资源路由名称
     * @var string
     */
    protected $resource;

    /**
     * 资源路由地址
     * @var string
     */
    protected $route;

    /**
     * REST方法定义
     * @var array
     */
    protected $rest = [];

    /**
     * Resource constructor.
     * @param Route $router 路由对象
     * @param RuleGroup|null $parent 上级对象
     * @param string $name 资源名称
     * @param string $route 路由地址
     * @param array $rest 资源定义
     */
    public function __construct(Route $router, RuleGroup $parent = null, string $name = '', string $route = '', array $rest = [])
    {
        $name = ltrim($name, '/');
        $this->router = $router;
        $this->parent = $parent;
        $this->resource = $name;
        $this->route = $route;
        $this->name = $name;

        $this->setFullName();

        $this->rest = $rest;

        if ($this->parent) {
            $this->parent->addRuleItem($this);
        }
    }

    /**
     * Note: 生成资源路由
     * Date: 2022-11-15
     * Time: 15:42
     * @return void
     */
    public function buildResourceRule()
    {
        $rule = $this->resource;
        $option = $this->option;
        $origin = $this->router->getGroup();
        $this->router->setGroup($this);

        foreach ($this->rest as $key => $val) {
            $ruleItem = $this->addRule(trim($val[1]), $this->route . '/' . $val[2], $val[0]);
        }

        $this->router->setGroup($origin);
    }

    /**
     * Note: 设置资源路由的变量
     * Date: 2023-07-13
     * Time: 11:25
     * @param array $vars 资源变量
     * @return $this
     */
    public function vars(array $vars)
    {
        return $this->setOption('var', $vars);
    }

    /**
     * Note: 设置资源允许
     * Date: 2023-07-13
     * Time: 11:27
     * @param array $only 资源允许
     * @return $this
     */
    public function only(array $only)
    {
        return $this->setOption('only', $only);
    }

    /**
     * Note: 设置资源排除
     * Date: 2023-07-13
     * Time: 11:29
     * @param array $except 排除资源
     * @return $this
     */
    public function except(array $except)
    {
        return $this->setOption('except', $except);
    }
}