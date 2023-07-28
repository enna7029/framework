<?php
declare(strict_types=1);

namespace Enna\Framework\Route;

use Enna\Framework\Helper\Str;
use Enna\Framework\Request;
use Enna\Framework\Response;
use Enna\Framework\Route;
use Enna\Framework\Route\Dispatch\Controller as ControllerDispatch;
use Enna\Framework\Route\Dispatch\Callback as CallbackDispatch;

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
     * @param string $name 域名名称
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
        //检测URL绑定(域名绑定)
        $result = $this->checkUrlBind($request, $url);

        //设置路由变量
        if (!empty($this->option['append'])) {
            $request->setRoute($this->option['append']);
            unset($this->option['append']);
        }

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
     * @return Dispatch|false
     */
    public function checkUrlBind(Request $request, string $url)
    {
        $bind = $this->router->getDomainBind($this->domain);

        if ($bind) {
            $this->parseBindAppendParam($bind);

            $type = substr($bind, 0, 1);
            $bind = substr($bind, 1);

            $bindTo = [
                '\\' => 'bindToClass',
                '@' => 'bindToController',
                ':' => 'bindToNamespace',
            ];

            if (isset($bindTo[$type])) {
                return $this->{$bindTo[$type]}($request, $url, $bind);
            }
        }

        return false;
    }

    /**
     * Note: 解析绑定并附加参数
     * Date: 2023-07-18
     * Time: 10:49
     * @param string $bind 绑定参数
     * @return void
     */
    protected function parseBindAppendParam(string &$bind)
    {
        if (strpos($bind, '?') !== false) {
            [$bind, $query] = explode('?', $bind);
            parse_str($query, $vars);
            $this->append($vars);
        }
    }

    /**
     * Note: 绑定到类
     * Date: 2023-07-18
     * Time: 11:28
     * @param Request $request 请求对象
     * @param string $url URL地址
     * @param string $class 类名
     * @return CallbackDispatch
     */
    protected function bindToClass(Request $request, string $url, string $class)
    {
        $array = explode('|', $url, 2);
        $action = !empty($array[0]) ? $array[0] : $this->router->config('default_action');

        $param = [];
        if (!empty($array[1])) {
            $this->parseUrlParams($array[1], $param);
        }

        return new CallbackDispatch($request, $this, [$class, $action], $param);
    }

    /**
     * Note: 绑定到控制器
     * Date: 2023-07-18
     * Time: 11:25
     * @param Request $request 请求对象
     * @param string $url URL地址
     * @param string $controller 控制器名
     * @return ControllerDispatch
     */
    protected function bindToController(Request $request, string $url, string $controller)
    {
        $array = explode('|', $url, 2);
        $action = !empty($array[0]) ? $array[0] : $this->router->config('default_action');

        $param = [];
        if (!empty($array[1])) {
            $this->parseUrlParams($array[1], $param);
        }

        return new ControllerDispatch($request, $this, $controller . '/' . $action, $param);
    }

    /**
     * Note: 绑定到命名空间
     * Date: 2023-07-18
     * Time: 11:28
     * @param Request $request 请求对象
     * @param string $url URL地址
     * @param string $namespace 命名空间
     * @return CallbackDispatch
     */
    protected function bindToNamespace(Request $request, string $url, string $namespace)
    {
        $array = explode('|', $url, 3);
        $class = !empty($array[0]) ? $array[0] : $this->router->config('default_controller');
        $action = !empty($array[1]) ? $array[1] : $this->router->config('default_action');

        $param = [];
        if (!empty($array[2])) {
            $this->parseUrlParams($array[2], $param);
        }

        return new CallbackDispatch($request, $this, [$namespace . '\\' . Str::studly($class), $action], $param);
    }

    /**
     * Note: 设置路由绑定
     * Date: 2023-07-18
     * Time: 11:45
     * @param string $bind 绑定信息
     * @return $this
     */
    public function bind(string $bind)
    {
        $this->router->bind($bind, $this->domain);

        return $this;
    }
}