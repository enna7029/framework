<?php
declare(strict_types=1);

namespace Enna\Framework;

use Enna\Framework\Log\Channel;
use Enna\Framework\Log\ChannelSet;
use http\Exception\InvalidArgumentException;

class Log extends Manager
{
    const EMERGENCY = 'Emergency';
    const ALERT = 'Alert';
    const CRITICAL = 'Critical';
    const ERROR = 'Error';
    const WARNING = 'Warning';
    const NOTICE = 'Notice';
    const INFO = 'info';
    const DEBUG = 'Debug';

    //驱动的命名空间
    protected $namespace = '\\Enna\\Framework\\Log\\Driver';

    /**
     * Note: 获取默认驱动
     * Date: 2022-12-07
     * Time: 10:11
     * @return array|mixed|null
     */
    public function getDefaultDriver()
    {
        return $this->getConfig('default');
    }

    /**
     * Note: 获取渠道类型
     * Date: 2022-12-07
     * Time: 10:12
     * @param string $name 渠道名称
     * @return mixed|void
     */
    public function resolveType(string $name)
    {
        return $this->getChannelConfig($name, 'type', 'file');
    }

    /**
     * Note: 获取渠道配置
     * Date: 2022-12-07
     * Time: 10:12
     * @param string $name 渠道名称
     * @return mixed|void
     */
    public function resolveConfig(string $name)
    {
        return $this->getChannelConfig($name);
    }

    /**
     * Note: 获取渠道配置
     * Date: 2022-12-07
     * Time: 10:34
     * @param string $channel 渠道名称
     * @param null $name 渠道下的配置名称
     * @param null $default 渠道下的配置默认值
     * @return string|array|null
     * @throws InvalidArgumentException
     */
    public function getChannelConfig($channel, $name = null, $default = null)
    {
        $config = $this->getConfig('channels.' . $channel);

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

        throw new InvalidArgumentException('渠道【' . $channel . '】未找到');
    }

    /**
     * Note: 获取日志配置
     * Date: 2022-12-06
     * Time: 15:30
     * @param string|null $name 名称
     * @param null $default 默认值
     */
    public function getConfig(string $name = null, $default = null)
    {
        if (!is_null($name)) {
            return $this->app->config->get('log.' . $name, $default);
        }

        return $this->app->config->get('log');
    }

    /**
     * Note: 获取渠道实例
     * Date: 2022-12-06
     * Time: 16:36
     * @param array|string|null $name 渠道名称
     * @return Channel|ChannelSet
     */
    public function channel($name = null)
    {
        if (is_array($name)) {
            return new ChannelSet($this, $name);
        }

        return $this->driver($name);
    }

    /**
     * Note: 记录日志信息
     * Date: 2022-09-20
     * Time: 16:42
     * @param mixed $msg 信息
     * @param string $type 级别
     * @param array $context 内容
     * @param bool $lazy 是否实时写入
     * @return $this
     */
    public function record($msg, string $type = 'info', array $context, bool $lazy = true)
    {
        $channel = $this->getConfig('type_channel.' . $type);
        
        $this->channel($channel)->record($msg, $type, $context, $lazy);

        return $this;
    }

    /**
     * Note: 创建驱动实例
     * Date: 2022-12-07
     * Time: 10:14
     * @param string $name 渠道名称
     * @return mixed|void
     */
    public function createDriver(string $name)
    {
        $driver = parent::createDriver($name);

        $lazy = !$this->getChannelConfig($name, 'realtime_write', false) && !$this->app->runningInConsole();
        $allow = array_merge($this->getConfig('level', []), $this->getChannelConfig($name, 'level', []));

        return new Channel($name, $driver, $allow, $lazy, $this->app->event);
    }
}