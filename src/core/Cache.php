<?php
declare(strict_types=1);

namespace Enna\Framework;

use Enna\Framework\Cache\TagSet;
use Psr\SimpleCache\CacheInterface;
use Enna\Framework\Cache\Driver;
use InvalidArgumentException;

/**
 * 缓存管理
 * Class Cache
 * @package Enna\Framework
 */
class Cache extends Manager
{
    protected $namespace = '\\Enna\\Framework\\Cache\\Driver\\';

    /**
     * Note: 默认驱动
     * Date: 2022-12-23
     * Time: 16:24
     * @return string|null
     */
    public function getDefaultDriver()
    {
        return $this->getConfig('default');
    }

    /**
     * Note: 获取缓存配置
     * Date: 2022-12-23
     * Time: 16:23
     * @param string|null $name 配置名称
     * @param mixed $default 默认值
     * @return mixed
     */
    public function getConfig(string $name = null, $default = null)
    {
        if (!is_null($name)) {
            return $this->app->config->get('cache.' . $name, $default);
        }

        return $this->app->config->get('cache');
    }

    /**
     * Note: 获取驱动配置
     * Date: 2022-12-23
     * Time: 17:05
     * @param string $store 驱动名称
     * @param string|null $name 驱动配置里的某个具体配置名
     * @param null $default 驱动配置里的配置默认值
     * @return array
     */
    public function getStoreConfig(string $store, string $name = null, $default = null)
    {
        $config = $this->getConfig('stores.' . $store);

        if ($config) {
            if (!is_null($name)) {
                foreach ($config as $key => $item) {
                    if ($name == $key && !empty($item)) {
                        return $item;
                    } else {
                        return $default;
                    }
                }
            } else {
                return $config;
            }
        }

        throw new InvalidArgumentException('驱动【' . $store . '】未找到');
    }

    /**
     * Note: 获取驱动类型
     * Date: 2022-12-07
     * Time: 10:12
     * @param string $name 渠道名称
     * @return mixed|void
     */
    public function resolveType(string $name)
    {
        return $this->getStoreConfig($name, 'type', 'file');
    }

    /**
     * Note: 获取驱动配置
     * Date: 2022-12-07
     * Time: 10:12
     * @param string $name 渠道名称
     * @return mixed|void
     */
    public function resolveConfig(string $name)
    {
        return $this->getStoreConfig($name);
    }

    /**
     * Note: 连接缓存驱动
     * Date: 2022-12-21
     * Time: 17:53
     * @param string|null $name
     * @return Driver
     */
    public function store(string $name = null)
    {
        return $this->driver($name);
    }

    /**
     * Note: 写入缓存
     * Date: 2022-12-21
     * Time: 17:51
     * @param string $key 缓存变量名
     * @param mixed $value 数据
     * @param int|\DateTime|null $ttl 有效时间 0:永久
     * @return bool
     */
    public function set($key, $value, $ttl = null)
    {
        return $this->store()->set($key, $value, $ttl);
    }

    /**
     * Note: 读取缓存
     * Date: 2022-12-23
     * Time: 18:24
     * @param string $key 缓存变量名
     * @param mixed|null $default 默认值
     * @return mixed|void
     */
    public function get(string $key, mixed $default = null)
    {
        return $this->store()->get($key, $default);
    }

    /**
     * Note: 删除缓存
     * Date: 2022-12-27
     * Time: 14:32
     * @param string $key 缓存变量名
     * @return bool
     */
    public function delete(string $key)
    {
        return $this->store()->delete($key);
    }

    /**
     * Note: 清除缓存
     * Date: 2022-12-27
     * Time: 14:33
     * @return bool
     */
    public function clear()
    {
        return $this->store()->clear();
    }

    /**
     * Note: 获取批量缓存
     * Date: 2022-12-28
     * Time: 18:36
     * @param iterable $keys 缓存变量名
     * @param mixed|null $default 默认值
     * @return iterable
     */
    public function getMultiple(iterable $keys, mixed $default = null)
    {
        return $this->store()->getMultiple($keys, $default);
    }

    /**
     * Note: 写入批量缓存
     * Date: 2022-12-28
     * Time: 18:38
     * @param iterable $values 缓存数据
     * @param int|\DateInterval|null $ttl 有效期 0:永久
     * @return bool
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null)
    {
        return $this->store()->setMultiple($values, $ttl);
    }

    /**
     * Note: 删除批量缓存
     * Date: 2022-12-28
     * Time: 18:38
     * @param iterable $keys 缓存变量名
     * @return bool
     */
    public function deleteMultiple(iterable $keys)
    {
        return $this->store()->deleteMultiple($keys);
    }

    /**
     * Note: 判断缓存是否存在
     * Date: 2022-12-28
     * Time: 16:53
     * @param string $key 缓存变量名
     * @return bool
     */
    public function has(string $key)
    {
        return $this->store()->has($key);
    }

    /**
     * Note: 缓存标签
     * Date: 2022-12-27
     * Time: 17:48
     * @param string|array $name 缓存标签
     * @return TagSet
     */
    public function tag($name)
    {
        return $this->store()->tag();
    }
}