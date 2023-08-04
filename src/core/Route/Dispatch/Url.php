<?php
declare(strict_types=1);

namespace Enna\Framework\Route\Dispatch;

use Enna\Framework\Helper\Str;
use Enna\Framework\Request;
use Enna\Framework\Route\Rule;
use Enna\Framework\Exception\HttpException;

/**
 * URL调度器:用户访问指定URL
 * Class Url
 * @package Enna\Framework\Route\Dispatch
 */
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
        $depr = $this->rule->config('pathinfo_depr');

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

        $var = [];
        if ($path) {
            preg_replace_callback('/(\w+)\|([^\|]+)/', function ($match) use (&$var) {
                $var[$match[1]] = strip_tags($match[2]);
            }, implode('|', $path));
        }

        // 设置当前请求的参数
        $this->param = $var;

        //路由
        $route = [$controller, $action];

        if ($this->hasDefineRoute($route)) {
            throw new HttpException(404, 'invalid request:' . str_replace('|', $depr, $url));
        }

        return $route;
    }

    /**
     * Note: 检查URL是否定义路由
     * Date: 2022-10-09
     * Time: 17:51
     * @param array $route 路由信息
     * @return bool
     */
    protected function hasDefineRoute(array $route)
    {
        [$controller, $action] = $route;

        $name = strtolower(Str::studly($controller) . '/' . $action);

        $host = $this->request->host(true);
        $method = $this->request->method();

        if ($this->rule->getRouter()->getName($name, $host, $method)) {
            return true;
        }

        return false;
    }
}