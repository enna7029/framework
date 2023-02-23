<?php

use Enna\Framework\Container;
use Enna\Framework\App;
use Enna\Framework\Validate;

if (!function_exists('app')) {
    /**
     * Note: 获取容器中的实例_支持依赖注入
     * Date: 2023-02-10
     * Time: 15:22
     * @param string $name 类名或标识
     * @param array $args 参数
     * @param bool $newInstance 是否每次创建新的实例
     * @return App|object
     */
    function app(string $name = '', array $args = [], bool $newInstance = false)
    {
        return Container::getInstance()->make($name ?: App::class, $args, $newInstance);
    }
}

if (!function_exists('validate')) {
    /**
     * Note: 生成验证对象
     * Date: 2023-02-10
     * Time: 15:21
     * @param string $validate 验证器
     * @param array $message 验证提示信息
     * @param bool $batch 是否批量
     * @param bool $failException 是否抛出异常
     * @return Validate
     */
    function validate($validate = '', array $message = [], bool $batch = false, bool $failException = true)
    {
        if ($validate === '') {
            $class = new Validate();
        } else {
            $class = new $validate();
        }

        return $class->message($message)->batch($batch)->failException($failException);
    }
}