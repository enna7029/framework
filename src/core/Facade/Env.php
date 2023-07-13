<?php
declare(strict_types=1);

namespace Enna\Framework\Facade;

use Enna\Framework\Facade;

/**
 * Class Env
 * @package Enna\Framework\Facade
 * @method load(string $file) 读取环境变量文件
 * @method set(string|array $env, mixed $value = null)  设置环境变量值
 * @method get(string $name = null, mixed $default = null) 获取环境变量值
 * @method has(string $name) 检测是否存在环境变量
 * @method __set(string $name, mixed $value) 设置环境变量
 * @method __get(string $name) 获取环境变量
 * @method __isset(string $name) 检查是否存在环境变量
 * @method offsetSet($name, $value)
 * @method offsetExists($name)
 * @method offsetUnset($name)
 * @method offsetGet($name)
 */
class Env extends Facade
{
    protected static function getFacadeClass()
    {
        return 'env';
    }
}

