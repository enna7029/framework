<?php
declare(strict_types=1);

namespace Enna\Framework;

use Enna\Orm\DbManager;

/**
 * 数据库管理类
 * Class Db
 * @package Enna\Framework
 */
class Db extends DbManager
{
    /**
     * Note:
     * Date: 2023-03-23
     * Time: 11:02
     * @param Event $event 
     * @param Config $config
     * @param Log $log
     * @param Cache $cache
     * @return static
     */
    public static function __make(Event $event, Config $config, Log $log, Cache $cache)
    {
        $db = new static();
        $db->setEvent($event);
        $db->setConfig($config);
        $db->setLog($log);

        $store = $db->getConfig('cache_store');
        $db->setCache($cache->store($store));

        return $db;
    }

    /**
     * Note: 设置事件对象
     * Date: 2023-03-22
     * Time: 18:15
     * @param Event $event 事件对象
     * @return void
     */
    public function setEvent($event)
    {
        $this->event = $event;
    }

    /**
     * Note: 注册回调方法
     * Date: 2023-03-22
     * Time: 18:27
     * @param string $event 事件名
     * @param callable $callback 回调方法
     * @return void
     */
    public function event(string $event, callable $callback)
    {
        if ($this->event) {
            $this->event->listen('db.' . $event, $callback);
        }
    }

    /**
     * Note: 触发事件
     * Date: 2023-03-22
     * Time: 18:28
     * @param string $event 事件名
     * @param mixed $params 传入参数
     * @param bool $once 只获取一个有效返回值
     * @return mixed|void
     */
    public function trigger(string $event, $params = null, bool $once = false)
    {
        if ($this->event) {
            $this->event->trigger($event, $params, $once);
        }
    }

    /**
     * Note: 设置配置对象
     * Date: 2023-03-22
     * Time: 18:10
     * @param Config $config 配置对象
     * @return void
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * Note: 获取配置参数值
     * Date: 2023-03-22
     * Time: 18:11
     * @param string|null $name 默认参数
     * @param mixed $default 默认值
     * @return array|mixed|void
     */
    public function getConfig(string $name = null, $default = null)
    {
        if (empty($name)) {
            return $this->config->get('database', []);
        }

        return $this->config->get('database.' . $name, $default);
    }

}