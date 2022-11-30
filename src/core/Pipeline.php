<?php

namespace Enna\Framework;

use Closure;
use Throwable;

class Pipeline
{
    /**
     * 请求类
     * @var Request
     */
    protected $request;

    /**
     * 中间件管道化
     * @var array
     */
    protected $pipes = [];

    /**
     * 异常处理器
     * @var callable
     */
    protected $exceptionHandler;

    /**
     * Note: 请求初始的请求类
     * Date: 2022-09-23
     * Time: 10:27
     * @param $request
     * @return $this
     */
    public function send($request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Note: 获取栈
     * Date: 2022-09-23
     * Time: 10:49
     * @param $pipes
     * @return $this
     */
    public function through($pipes)
    {
        $this->pipes = $pipes;

        return $this;
    }

    /**
     * Note: 执行栈
     * Date: 2022-09-23
     * Time: 10:53
     * @param Closure $destination
     * @return mixed
     */
    public function then(Closure $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            function ($request) use ($destination) {
                try {
                    return $destination($request);
                } catch (Throwable | Exception $e) {
                    return $this->handleException($request, $e);
                }
            }
        );

        return $pipeline($this->request);
    }

    /**
     * Note: 使用回调执行栈中的中间件
     * Date: 2022-09-23
     * Time: 10:54
     */
    public function carry()
    {
        return function ($stack, $pipe) {
            return function ($request) use ($stack, $pipe) {
                try {
                    return $pipe($request, $stack);
                } catch (Throwable | Exception $e) {
                    return $this->handleException($request, $e);
                }
            };
        };
    }

    /**
     * Note: 设置异常处理器
     * Date: 2022-09-23
     * Time: 11:02
     * @param callable $handler
     */
    public function whenException($handler)
    {
        $this->exceptionHandler = $handler;

        return $this;
    }

    /**
     * Note: 异常处理
     * Date: 2022-09-23
     * Time: 11:06
     * @param Request $reqeust 请求类
     * @param Throwable $e
     */
    public function handleException($reqeust, Throwable $e)
    {
        if ($this->exceptionHandler) {
            return call_user_func($this->exceptionHandler, $reqeust, $e);
        }
        throw $e;
    }
}