<?php
declare(strict_types=1);

namespace Enna\Framework\Cache;

use Enna\Framework\Container;
use Enna\Framework\Contract\CacheHandlerInterface;
use http\Exception\InvalidArgumentException;
use SebastianBergmann\FileIterator\Iterator;
use Exception;
use Throwable;
use Closure;

abstract class Driver implements CacheHandlerInterface
{
    /**
     * 驱动句柄
     * @var null|Object
     */
    protected $handler = null;

    /**
     * 缓存读取次数
     * @var int
     */
    protected $readTimes = 0;

    /**
     * 缓存写入次数
     * @var int
     */
    protected $writeTimes = 0;

    /**
     * 缓存参数
     * @var array
     */
    protected $options = [];

    /**
     * 缓存标签
     * @var array
     */
    protected $tag = [];

    /**
     * Note: 获取有效期
     * Date: 2022-12-24
     * Time: 15:01
     * @param int|\DateTimeInterface $expire 有效期
     * @return int
     */
    protected function getExpireTime($expire)
    {
        if ($expire instanceof \DateTimeInterface) {
            $expire = $expire->getTimestamp() - time();
        }

        return (int)$expire;
    }

    /**
     * Note: 获取缓存标识
     * Date: 2022-12-27
     * Time: 14:40
     * @param string $name 缓存变量名
     * @return string
     */
    public function getCacheKey(string $name)
    {
        return $this->options['prefix'] . $name;
    }

    /**
     * Note: 写入批量缓存
     * Date: 2022-12-27
     * Time: 15:29
     * @param iterable $values 缓存数据
     * @param null|int $ttl 有效期 0:永久
     * @return bool
     */
    public function setMultiple($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $result = $this->set($key, $value, $ttl);

            if ($result === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Note: 获取批量缓存
     * Date: 2022-12-27
     * Time: 15:36
     * @param iterable $keys 缓存变量名
     * @param mixed $default 默认值
     * @return iterable
     */
    public function getMultiple($keys, $default = null)
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * Note: 删除批量缓存
     * Date: 2022-12-27
     * Time: 15:39
     * @param iterable $keys 缓存变量名
     * @return bool
     */
    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $result = $this->delete($key);

            if ($result === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Note: 缓存标签
     * Date: 2022-12-27
     * Time: 15:54
     * @param string|array $name 标签名
     * @return TagSet
     */
    public function tag($name)
    {
        $name = (array)$name;
        $key = implode('-', $name);

        if (!isset($this->tag[$key])) {
            $this->tag[$key] = new TagSet($name, $this);
        }

        return $this->tag[$key];
    }

    /**
     * Note: 获取标签包含的缓存标识
     * Date: 2022-12-27
     * Time: 17:55
     * @param string $tag 标签名
     * @return array
     */
    public function getTagItems(string $tag)
    {
        $name = $this->getTagKey($tag);

        return $this->get($name, []);
    }

    /**
     * Note: 获取实际标签名
     * Date: 2022-12-27
     * Time: 17:56
     * @param string $tag 标签名
     * @return string
     */
    public function getTagKey(string $tag)
    {
        return $this->options['tag_prefix'] . md5($tag);
    }

    /**
     * Note: 追加缓存标识到标签
     * Date: 2022-12-27
     * Time: 18:33
     * @param string $name 缓存变量名
     * @param string $value 存储数据
     * @return void
     */
    public function append(string $name, $value)
    {
        $this->push($name, $value);
    }

    /**
     * Note: 追加缓存
     * Date: 2022-12-27
     * Time: 18:36
     * @param string $name
     * @param $value
     */
    public function push(string $name, $value)
    {
        $item = $this->get($name, []);

        if (!is_array($item)) {
            throw new InvalidArgumentException('只允许追加数组格式的缓存');
        }

        $item[] = $value;

        if (count($item) > 1000) {
            array_shift($item);
        }

        $item = array_unique($item);

        $this->set($name, $item);
    }

    /**
     * Note: 读取并删除缓存
     * Date: 2022-12-28
     * Time: 18:28
     * @param string $name 缓存变量名
     * @return mixed
     */
    public function pull(string $name)
    {
        $result = $this->get($name, false);

        if ($result) {
            $this->delete($name);
            return $result;
        }
    }

    /**
     * Note: 不存在则写入缓存
     * Date: 2022-12-28
     * Time: 17:18
     * @param string $name 缓存变量名
     * @param mixed $value 存储数据
     * @param int|null $expire 有效期 0:永久
     * @return mixed
     */
    public function remember(string $name, $value, $expire = null)
    {
        if ($this->has($name)) {
            return $this->get($name);
        }

        $time = time();
        if ($time + 5 > time() && $this->has($name . '_locl')) {
            usleep(200000);
        }

        try {
            $this->set($name . '_lock', true);

            if ($value instanceof Closure) {
                $value = Container::getInstance()->invokeFunction($value);
            }

            $this->set($name, $value, $expire);

            $this->delete($name . '_lock');
        } catch (Exception | Throwable) {
            $this->delete($name . '_lock');
            throw $e;
        }

        return $value;
    }

    /**
     * Note: 序列化数据
     * Date: 2022-12-24
     * Time: 16:17
     * @param mixed $data 缓存数据
     * @return string
     */
    protected function serialize($data)
    {
        if (is_numeric($data)) {
            return (string)$data;
        }

        $serialize = $this->options['serialize'][0] ?? 'serialize';

        return $serialize($data);
    }

    /**
     * Note: 反序列化数据
     * Date: 2022-12-24
     * Time: 16:19
     * @param string $data 缓存数据
     * @return mixed
     */
    protected function unserialize($data)
    {
        if (is_numeric($data)) {
            return $data;
        }

        $unserialize = $this->options['serialize'][1] ?? 'unserialize';

        return $unserialize($data);
    }

    /**
     * Note: 获取缓存读取次数
     * Date: 2022-12-27
     * Time: 14:45
     * @return int
     */
    public function getReadTimes()
    {
        return $this->readTimes;
    }

    /**
     * Note: 获取缓存写入次数
     * Date: 2022-12-27
     * Time: 14:45
     * @return int
     */
    public function getWriteTimes()
    {
        return $this->writeTimes;
    }

    /**
     * Note: 获取驱动句柄
     * Date: 2022-12-27
     * Time: 14:41
     * @return Object|null
     */
    public function handler()
    {
        return $this->handler;
    }

    public function __call($method, $args)
    {
        return call_user_func_array([$this->handler, $method], $args);
    }
}