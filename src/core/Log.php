<?php
declare(strict_types=1);

namespace Enna\Framework;

use Enna\Framework\Log\Channel;
use Enna\Framework\Log\ChannelSet;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

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
    protected $namespace = '\\Enna\\Framework\\Log\\Driver\\';

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
    public function record($msg, string $type = 'info', array $context = [], bool $lazy = true)
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

    /**
     * Note: 保存日志信息
     * Date: 2022-12-09
     * Time: 16:50
     * @return bool
     */
    public function save()
    {
        /**
         * @var Channel $channel
         */
        foreach ($this->drivers as $channel) {
            $channel->save();
        }

        return true;
    }

    /**
     * Note: 记录日志信息
     * Date: 2022-12-09
     * Time: 17:08
     * @param string $level 日志级别
     * @param mixed $message 日志信息
     * @param array $context 上下文内容
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        $this->record($message, $level, $context);
    }

    /**
     * Note: 记录emergency信息
     * Date: 2022-12-23
     * Time: 14:27
     * @param string|\Stringable $message 日志信息
     * @param array $context 上下文内容
     * @return void
     */
    public function emergency($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * Note: 记录alert信息
     * Date: 2022-12-23
     * Time: 14:30
     * @param string|\Stringable $message 日志信息
     * @param array $context 上下文内容
     * @return void
     */
    public function alert($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * Note: 记录critical信息
     * Date: 2022-12-23
     * Time: 14:31
     * @param string|\Stringable $message 日志信息
     * @param array $context 上下文内容
     * @return void
     */
    public function critical($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * Note: 记录error信息
     * Date: 2022-12-23
     * Time: 14:45
     * @param string|\Stringable $message 日志信息
     * @param array $context 上下文内容
     * @return void
     */
    public function error($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * Note: 记录warning信息
     * Date: 2022-12-23
     * Time: 14:46
     * @param string|\Stringable $message 日志内容
     * @param array $context 上下文内容
     * @return void
     */
    public function warning($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * Note: 记录notice信息
     * Date: 2022-12-23
     * Time: 14:47
     * @param string|\Stringable $message 日志内容
     * @param array $context 上下文信息
     * @return void
     */
    public function notice($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * Note: 记录info信息
     * Date: 2022-12-23
     * Time: 14:47
     * @param string|\Stringable $message 日志内容
     * @param array $context 上下文信息
     * @return void
     */
    public function info($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * Note: 记录debug信息
     * Date: 2022-12-23
     * Time: 14:49
     * @param string|\Stringable $message 日志内容
     * @param array $context 上下文信息
     * @return void
     */
    public function debug($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }


    public function __call($method, $arguments)
    {
        $this->log($method, ...$arguments);
    }
}