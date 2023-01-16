<?php
declare(strict_types=1);

namespace Enna\Framework\Cache\Driver;

use Enna\Framework\Cache\Driver;

/**
 * Redis缓存驱动
 *
 * 需要安装phpredis扩展(C语言包)或者predis扩展(PHP语言包)
 *
 * phpredis扩展:https://github.com/phpredis/phpredis
 * 使用:$redis = new Redis();
 *
 * predis扩展:https://github.com/predis/predis
 * 使用:$redis = new Predis\Client();
 *
 * Class Redis
 */
class Redis extends Driver
{
    /**
     * @var \Predis\Client|\Redis
     */
    protected $handler;

    /**
     * 配置参数
     * @var array
     */
    protected $options = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
        'select' => 0,
        'expire' => 0,
        'timeout' => 0,
        'persistent' => false,
        'prefix' => '',
        'tag_prefix' => '',
        'serialize' => '',
    ];

    public function __construct(array $options = [])
    {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }

        if (extension_loaded('redis')) { //phpredis
            $this->handler = new \Redis();

            if ($this->options['persistent']) {
                $this->handler->pconnect($this->options['host'], (int)$this->options['port'], (int)$this->options['timeout'], 'persistent_id_' . $this->options['select']);
            } else {
                $this->handler->connect($this->options['host'], (int)$this->options['port'], (int)$this->options['timeout']);
            }

            if ($this->options['password'] != '') {
                $this->handler->auth($this->options['password']);
            }

            if ($this->options['select'] != 0) {
                $this->handler->select((int)$this->options['select']);
            }
        } elseif (class_exists('\Predis\Client')) { //predis
            $params = [];
            foreach ($this->options as $key => $value) {
                if (in_array($key, ['prefix', 'exceptions', 'connections', 'cluster', 'replication', 'aggregate', 'parameters', 'commands'])) {
                    $params[$key] = $value;
                    unset($this->options[$key]);
                }
            }

            if ($this->options['password'] == '') {
                unset($this->options['password']);
            }
            if ($this->options['select'] != 0) {
                $this->options['database'] = $this->options['select'];
            }

            $this->handler = new \Predis\Client($this->options, $params);
        } else {
            throw new \BadFunctionCallException('不支持驱动:redis');
        }
    }

    /**
     * Note: 写入缓存
     * Date: 2023-01-05
     * Time: 16:39
     * @param string $name 缓存变量名
     * @param mixed $value 缓存数据
     * @param int|\DateTime $expire 有效时间(秒) 0:永久
     * @return bool
     */
    public function set($name, $value, $expire = null)
    {
        $this->writeTimes++;

        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }

        $expire = $this->getExpireTime($expire);
        $key = $this->getCacheKey($name);
        $value = $this->serialize($value);

        if ($expire) {
            $this->handler->setex($name, $expire, $value);
        } else {
            $this->handler->set($name, $value);
        }

        return true;
    }

    /**
     * Note: 读取缓存
     * Date: 2023-01-05
     * Time: 17:02
     * @param string $name 缓存变量名
     * @param mixed $default 缓存默认值
     * @return mixed
     */
    public function get($name, $default = null)
    {
        $this->readTimes++;
        $key = $this->getCacheKey($name);
        $value = $this->handler->get($name);

        if (is_null($value) || $value == false) {
            $value = $default;
        }

        return $this->unserialize($value);
    }

    /**
     * Note: 判断缓存是否存在
     * Date: 2023-01-05
     * Time: 17:18
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return $this->handler->exists($this->getCacheKey($name)) ? true : false;
    }

    /**
     * Note: 自增缓存(针对数值缓存)
     * Date: 2023-01-05
     * Time: 17:28
     * @param string $name 缓存变量名
     * @param int $step 步长
     * @return false|int|void
     */
    public function inc($name, int $step = 1)
    {
        $this->writeTimes++;
        $key = $this->getCacheKey($name);

        return $this->handler->incrby($key, $step);
    }

    /**
     * Note: 自减缓存(针对数值缓存)
     * Date: 2023-01-05
     * Time: 17:33
     * @param string $name 缓存变量名
     * @param int $step 步长
     * @return false|int|void
     */
    public function dec($name, int $step = 1)
    {
        $this->writeTimes++;
        $key = $this->getCacheKey($name);

        return $this->handler->decrby($key, $step);
    }

    /**
     * Note: 删除缓存
     * Date: 2023-01-05
     * Time: 18:03
     * @param string $name 缓存变量名
     * @return bool|void
     */
    public function delete($name)
    {
        $this->writeTimes++;
        $key = $this->getCacheKey($name);

        $number = $this->handler->del($key);
        return $number > 0;
    }

    /**
     * Note: 清除缓存
     * Date: 2023-01-05
     * Time: 18:07
     * @return bool|void
     */
    public function clear()
    {
        $this->writeTimes++;
        $this->handler->flushDB();

        return true;
    }

    /**
     * Note: 删除缓存标签
     * Date: 2023-01-05
     * Time: 18:11
     * @param array $keys
     * @return bool|void
     */
    public function clearTag(array $keys)
    {
        foreach ($keys as $key) {
            $this->handler->del($key);
        }
    }
}