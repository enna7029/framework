<?php
declare(strict_types=1);

namespace Enna\Framework\Facade;

use Enna\Framework\Facade;
use Enna\Framework\Route\RuleGroup;
use Enna\Framework\Route\RuleItem;
use Enna\Framework\Route\Url;

/**
 * Class Route
 * @package Enna\Framework\Facade
 * @method static RuleGroup group(string|\Closure $name, mixed $route = null) 注册路由分组
 * @method static RuleItem get(string $rule, mixed $route) 注册GET路由
 * @method static RuleItem post(string $rule, mixed $route) 注册POST路由
 * @method static RuleItem rule(string $rule, mixed $route) 注册路由规则
 * @method static RuleItem miss(string|\Closure $route, string $method = '*') 注册未匹配路由规则后的处理
 * @method static Url      buildUrl(string $url = '', array $vars = []) URL生成 支持路由反射
 */
class Route extends Facade
{
    public static function getFacadeClass()
    {
        return 'route';
    }
}