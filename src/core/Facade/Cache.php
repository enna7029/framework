<?php
declare(strict_types=1);

namespace Enna\Framework\Facade;

use Enna\Framework\Cache\Driver;
use Enna\Framework\Facade;
use http\Message\Body;

/**
 * Class Cache
 * @package Enna\Framework\Facade
 * @method static string|null getDefaultDriver() 默认驱动
 * @method static mixed getConfig() 获取缓存配置
 * @method static array getStoreConfig() 获取驱动配置
 * @method static mixed|null resolveType() 获取驱动类型
 * @method static mixed|void resolveConfig() 获取驱动配置
 * @method static Driver store() 连接缓存驱动
 * @method static bool set(string $key, mixed $value, int $ttl = null) 写入缓存
 */
class Cache extends Facade
{
    protected static function getFacadeClass()
    {
        return 'cache';
    }
}