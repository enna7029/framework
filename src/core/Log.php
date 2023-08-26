<?php
declare(strict_types=1);

namespace Enna\Framework;

use Enna\Framework\Event\LogWrite;
use Enna\Framework\Log\Channel;
use Enna\Framework\Log\ChannelSet;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Enna\Framework\Helper\Arr;

class Log extends Manager implements LoggerInterface
{
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';
    const SQL = 'sql';

    //驱动的命名空间
    protected $namespace = '\\Enna\\Framework\\Log\\Driver\\';

    /**
     * Note: 获取通道实例
     * Date: 2022-12-06
     * Time: 16:36
     * @param array|string|null $name 通道名称
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
     * Note: 获取日志配置
     * Date: 2022-12-06
     * Time: 15:30
     * @param string|null $name 名称
     * @param mixed $default 默认值
     * @return mixed
     */
    public function getConfig(string $name = null, $default = null)
    {
        if (!is_null($name)) {
            return $this->app->config->get('log.' . $name, $default);
        }

        return $this->app->config->get('log');
    }

    /**
     * Note: 获取通道类型
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
     * Note: 获取通道配置
     * Date: 2022-12-07
     * Time: 10:12
     * @param string $name 渠道名称
     * @return mixed
     */
    public function resolveConfig(string $name)
    {
        return $this->getChannelConfig($name);
    }

    /**
     * Note: 获取通道配置
     * Date: 2022-12-07
     * Time: 10:34
     * @param string $channel 渠道名称
     * @param string $name 渠道下的配置名称
     * @param mixed $default 渠道下的配置默认值
     * @return string|array|null
     * @throws InvalidArgumentException
     */
    public function getChannelConfig($channel, $name = null, $default = null)
    {
        if ($config = $this->getConfig('channels.' . $channel)) {
            return Arr::get($config, $name, $default);
        }

        throw new InvalidArgumentException('通道【' . $channel . '】未找到');
    }

    /**
     * Note: 创建驱动实例
     * Date: 2022-12-07
     * Time: 10:14
     * @param string $name 通道名称
     * @return mixed|void
     */
    public function createDriver(string $name)
    {
        $driver = parent::createDriver($name); //获取驱动对象实例

        $lazy = !$this->getChannelConfig($name, 'realtime_write', false) && !$this->app->runningInConsole();
        
        $allow = array_merge($this->getConfig('level', []), $this->getChannelConfig($name, 'level', []));

        return new Channel($name, $driver, $allow, $lazy, $this->app->event);
    }

    /**
     * Note: 清空日志信息
     * Date: 2023-08-26
     * Time: 15:42
     * @param string $channel 日志通道
     * @return $this
     */
    public function clear($channel = '*')
    {
        if ($channel == '*') {
            $channel = array_keys($this->drivers);
        }

        $this->channel($channel)->clear();

        return $this;
    }

    /**
     * Note: 关闭本次请求日志写入
     * Date: 2023-08-26
     * Time: 15:44
     * @return $this
     */
    public function close()
    {
        if ($channel == '*') {
            $channel = array_keys($this->drivers);
        }

        $this->channel($channel)->close();

        return $this;
    }

    /**
     * Note: 获取日志信息
     * Date: 2023-08-26
     * Time: 15:45
     * @param string $channel 日志通道
     * @return mixed
     */
    public function getLog(string $channel = null)
    {
        return $this->channel($channel)->getLog();
    }

    /**
     * Note: 注册日志写入事件
     * Date: 2023-08-26
     * Time: 15:34
     * @param mixed $listener 监听类(或闭包)
     * @return Event
     */
    public function listen($listener)
    {
        return $this->app->event->listen(LogWrite::class, $listener);
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
     * Note: 实时写入日志信息
     * Date: 2023-08-26
     * Time: 15:37
     * @param mixed $msg 信息
     * @param string $type 级别
     * @param array $context 内容
     * @return $this
     */
    public function write($msg, string $type = 'info', array $context = [])
    {
        return $this->record($msg, $type, $context, false);
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
    public function log($level, $message, array $context = []): void
    {
        $this->record($message, $level, $context);
    }

    /**
     * Note: 记录emergency信息
     * Date: 2022-12-23
     * Time: 14:27
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public function emergency($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * Note: 记录alert信息
     * Date: 2022-12-23
     * Time: 14:30
     * @param mixed $message 日志信息
     * @param array $context 上下文内容
     * @return void
     */
    public function alert($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * Note: 记录critical信息
     * Date: 2022-12-23
     * Time: 14:31
     * @param mixed $message 日志信息
     * @param array $context 上下文内容
     * @return void
     */
    public function critical($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * Note: 记录error信息
     * Date: 2022-12-23
     * Time: 14:45
     * @param mixed $message 日志信息
     * @param array $context 上下文内容
     * @return void
     */
    public function error($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * Note: 记录warning信息
     * Date: 2022-12-23
     * Time: 14:46
     * @param mixed $message 日志内容
     * @param array $context 上下文内容
     * @return void
     */
    public function warning($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * Note: 记录notice信息
     * Date: 2022-12-23
     * Time: 14:47
     * @param mixed $message 日志内容
     * @param array $context 上下文信息
     * @return void
     */
    public function notice($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * Note: 记录info信息
     * Date: 2022-12-23
     * Time: 14:47
     * @param mixed $message 日志内容
     * @param array $context 上下文信息
     * @return void
     */
    public function info($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * Note: 记录debug信息
     * Date: 2022-12-23
     * Time: 14:49
     * @param mixed $message 日志内容
     * @param array $context 上下文信息
     * @return void
     */
    public function debug($message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * Note: 记录sql信息
     * Date: 2023-08-26
     * Time: 11:12
     * @param mixed $message 日志信息
     * @param array $context 替换内容
     * @return void
     */
    public function sql($message, array $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function __call($method, $arguments)
    {
        $this->log($method, ...$arguments);
    }
}