<?php
declare(strict_types=1);

namespace Enna\Framework\Contract;

/**
 * 缓存驱动接口
 * Interface CacheHandlerInterface
 * @package Enna\Framework\Contract
 */
interface CacheHandlerInterface
{
    /**
     * Note: 判断缓存
     * Date: 2022-12-23
     * Time: 15:41
     * @param string $name 缓存变量名
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * Note: 读取缓存
     * Date: 2022-12-23
     * Time: 15:42
     * @param string $name 缓存变量名
     * @param mixed $default 默认值
     * @return bool
     */
    public function get(string $name,mixed $default = null);

    /**
     * Note: 设置缓存
     * Date: 2022-12-23
     * Time: 15:42
     * @param string $name 缓存变量名
     * @param mixed $value 缓存值
     * @param int $expire 有效时间(秒)
     * @return bool
     */
    public function set(string $name, mixed $value, \DateInterval|int|null $expire = null);

    /**
     * Note: 自增缓存
     * Date: 2022-12-23
     * Time: 15:45
     * @param string $name 缓存变量名
     * @param int $step 步长
     * @return false|int
     */
    public function inc($name, int $step = 1);

    /**
     * Note: 自减缓存
     * Date: 2022-12-23
     * Time: 15:46
     * @param string $name 缓存变量名称
     * @param int $step 步长
     * @return false|int
     */
    public function dec($name, int $step = 1);

    /**
     * Note: 删除缓存
     * Date: 2022-12-23
     * Time: 15:48
     * @param string $name 缓存变量名
     * @return bool
     */
    public function delete(string $name);

    /**
     * Note: 清除缓存
     * Date: 2022-12-23
     * Time: 15:48
     * @return bool
     */
    public function clear();

    /**
     * Note: 清楚缓存标签
     * Date: 2022-12-23
     * Time: 15:49
     * @param array $keys 缓存标识列表
     * @return bool
     */
    public function clearTag(array $keys);
}