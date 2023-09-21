<?php

namespace Enna\Framework;

/**
 * Facade管理类
 * Class Facade
 * @package Enna\Framework
 */
class Facade
{
    /**
     * @var bool
     */
    protected static $newInstance;

    /**
     * Note: 获取facade对应的类名
     * Date: 2022-10-22
     * Time: 10:49
     */
    protected static function getFacadeClass()
    {
    }

    /**
     * Note: 创建facade实例
     * Date: 2022-10-22
     * Time: 10:41
     * @param string $class
     * @param array $args
     * @param bool $newInstance
     */
    protected static function createFacade(string $class = '', array $args = [], bool $newInstance = false)
    {
        $class = $class ?: static::class;

        $facadeClass = static::getFacadeClass();
        if ($facadeClass) {
            $class = $facadeClass;
        }

        if (static::$newInstance) {
            $newInstance = true;
        }

        return Container::getInstance()->make($class, $args, $newInstance);
    }

    //在静态上下文中调用不可访问的方法时调用
    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array([static::createFacade(), $name], $arguments);
    }
}