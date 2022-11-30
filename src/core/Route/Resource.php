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
}