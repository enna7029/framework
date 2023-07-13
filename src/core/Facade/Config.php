<?php
declare(strict_types=1);

namespace Enna\Framework\Facade;

use Enna\Framework\Facade;

/**
 * Class Config
 * @package Enna\Framework\Facade
 * @method load(string $file, string $name = '') 加载配置文件
 * @method parse(string $file, string $name) 解析配置文件
 * @method set(array $config, string $name) 设置配置参数
 * @method get(string $name, $default = null) 获取配置参数,带默认值
 * @method has(string $name) 检查配置是否存在
 */
class Config extends Facade
{
    protected static function getFacadeClass()
    {
        return 'config';
    }
}