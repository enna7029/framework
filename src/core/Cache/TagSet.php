<?php
declare(strict_types=1);

namespace Enna\Framework\Cache;

/**
 * 标签集合类
 * Class TagSet
 * @package Enna\Framework\Cache
 */
class TagSet
{
    /**
     * 缓存标签
     * @var array
     */
    protected $tag;

    /**
     * 缓存句柄
     * @var Driver
     */
    protected $handler;

    public function __construct(array $tag, Driver $driver)
    {
        $this->tag = $tag;
        $this->handler = $driver;
    }

    /**
     * Note: 写入缓存
     * Date: 2022-12-27
     * Time: 16:01
     * @param string $name 缓存变量名
     * @param mixed $value 存储数据
     * @param null|int|\DateTime $expire 有效期 0:永久
     * @return bool
     */
    public function set(string $name, $value, $expire = null)
    {
        $this->handler->set($name, $value, $expire);

        $this->append($name);

        return true;
    }

    /**
     * Note: 不存在则写入缓存
     * Date: 2022-12-28
     * Time: 18:34
     * @param string $name 缓存变量名
     * @param mixed $value 存储数据
     * @param null|int|\DateTime $expire有效期 0:永久
     */
    public function remember(string $name, $value, $expire = null)
    {
        $this->handler->remember($name, $value, $expire);

        $this->append($name);

        return true;
    }

    /**
     * Note: 追加缓存标识到标签
     * Date: 2022-12-27
     * Time: 17:44
     * @param string $name 缓存变量名
     * @return void
     */
    public function append(string $name)
    {
        $name = $this->handler->getCacheKey($name);

        foreach ($this->tag as $tag) {
            $key = $this->handler->getTagKey($tag);
            $this->handler->append($key, $name);
        }
    }

    /**
     * Note: 清除标签的缓存数据
     * Date: 2022-12-28
     * Time: 10:52
     * @return bool
     */
    public function clear()
    {
        foreach ($this->tag as $tag) {
            $names = $this->handler->getTagItems($tag);
            $this->handler->clearTag($names);

            $key = $this->handler->getTagKey($tag);
            $this->handler->delete($key);
        }
    }
}