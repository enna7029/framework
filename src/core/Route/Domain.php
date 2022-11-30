<?php
declare(strict_types=1);

namespace Enna\Framework\Route;

use Enna\Framework\Request;
use Enna\Framework\Response;
use Enna\Framework\Route;

/**
 * 域名路由
 * Class Domain
 * @package Enna\Framework\Route
 */
class Domain extends RuleGroup
{
    /**
     * Domain constructor.
     * @param Route $router 路由对象
     * @param string $name 路由域名
     * @param mixed $rule 域名路由
     */
    public function __construct(Route $router, string $name = null, $rule = null)
    {
        $this->router = $router;
        $this->domain = $name;
        $this->rule = $rule;
    }

    /**
     * Note: 检测域名路由
     * Date: 2022-10-29
     * Time: 9:53
     * @param Request $request 请求对象
     * @param string $url 访问地址
     * @param bool $completeMatch 路由是否完全匹配
     * @return Dispatch|false
     */
    public function check(Request $request, string $url, bool $completeMatch = false)
    {
        $result = $this->checkUrlBind($request, $url);

        if ($result !== false) {
            return $result;
        }

        return parent::check($request, $url, $completeMatch);
    }

    /**
     * Note: 检测URL绑定
     * Date: 2022-10-29
     * Time: 10:08
     * @param Request $request 请求对象
     * @param string $url url地址
     * @return false
     */
    public function checkUrlBind(Request $request, string $url)
    {
        return false;
    }
}