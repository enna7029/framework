<?php
declare(strict_types=1);

namespace Enna\Framework\Middleware;

use Enna\Framework\Exception\ValidateException;
use Enna\Framework\Request;
use Enna\Framework\Response;

/**
 * 表单令牌检测
 * Class FormTokenCheck
 * @package Enna\Framework\Middleware
 */
class FormTokenCheck
{
    /**
     * Note: 表单令牌检测
     * Date: 2023-07-07
     * Time: 14:37
     * @param Request $request 请求对象
     * @param \Closure $next 闭包(请求闭包)
     * @param string $token 表单令牌Token名称
     * @return Response
     */
    public function handle(Request $request, \Closure $next, string $token = null)
    {
        $check = $request->checkToken($token ?: '__token__');

        if ($check === false) {
            throw new ValidateException('invalid token');
        }

        return $next($request);
    }
}