<?php
declare(strict_types=1);

namespace Enna\Framework\Route\Dispatch;

use Enna\Framework\Request;
use Enna\Framework\Route\Rule;
use Enna\Framework\Exception\HttpException;

class Url extends Controller
{
    public function __construct(Request $request, Rule $rule, $dispatch)
    {
        $this->request = $request;
        $this->rule = $rule;
        $dispatch = $this->parseUrl($dispatch);

        parent::__construct($request, $rule, $dispatch, $this->param);
    }

    /**
     * Note: 解析URL
     * Date: 2022-09-30
     * Time: 18:05
     * @param string $url URL
     * @return array
     */
    protected function parseUrl(string $url)
    {
        $path = $this->rule->parseUrlPath($url);
        if (empty($path)) {
            return [null, null];
        }

        //解析控制器
        $controller = !empty($path) ? array_shift($path) : null;
        if ($controller && !preg_match('/^[a-zA-Z0-9][\w|\.]*$/', $controller)) {
            throw new HttpException(404, 'controller not exists:' . $controller);
        }

        //解析方法
        $action = !empty($path) ? array_shift($path) : null;

        // 设置当前请求的参数
        $this->param = [];
        
        //路由
        $route = [$controller, $action];

        if ($this->hasDefineRoute($route)) {
            throw new HttpException(404, 'invalid request:' . $url);
        }

        return $route;
    }

    /**
     * Note: 检查URL是否定义路由
     * Date: 2022-10-09
     * Time: 17:51
     * @param array $route
     * @return bool
     */
    protected function hasDefineRoute(array $route)
    {
        [$controller, $action] = $route;

//        $name = strtolower($controller . '/' . $action);
//        $host = $this->request->host(true);
//        $method = $this->request->method();

        return false;
    }
}