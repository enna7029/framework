<?php
declare(strict_types=1);

namespace Enna\Framework\Facade;

use Enna\Framework\Facade;
use Enna\Framework\Route\RuleGroup;
use Enna\Framework\Route\RuleItem;

/**
 * Class Route
 * @package Enna\Framework\Facade
 * @method static RuleGroup group(string|\Closure $name, mixed $route = null)
 * @method static RuleItem get(string $rule, mixed $route)
 * @method static RuleItem post(string $rule, mixed $route)
 * @method static RuleItem rule(string $rule, mixed $route)
 * @method static RuleItem miss(string|\Closure $route, string $method = '*')
 */
class Route extends Facade
{
    public static function getFacadeClass()
    {
        return 'route';
    }
}