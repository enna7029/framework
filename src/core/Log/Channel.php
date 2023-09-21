<?php

namespace Enna\Framework\Log;

use Psr\Log\LoggerInterface;
use Enna\Framework\Contract\LogHandlerInterface;
use Enna\Framework\Event;
use Enna\Framework\Event\LogRecord;
use Enna\Framework\Event\LogWrite;

class Channel
{
    /**
     * 通道名称
     * @var string
     */
    protected $name;

    /**
     * 驱动
     * @var LogHandlerInterface
     */
    protected $logger;

    /**
     * 允许记录的日志级别
     * @var array
     */
    protected $allow;

    /**
     * 是否延迟写入
     * @var bool
     */
    protected $lazy = true;

    /**
     * 事件
     * @var Event
     */
    protected $event;

    /**
     * 关闭日志
     * @var bool
     */
    protected $close = false;

    /**
     * 日志信息
     * @var array
     */
    protected $log = [];

    /**
     * Channel constructor.
     * @param string $name 通道名称
     * @param LogHandlerInterface $logger 驱动对象实例
     * @param array $allow 允许记录的日志类型
     * @param bool $lazy 是否延迟写入
     * @param Event $envent 事件
     */
    public function __construct(string $name, LogHandlerInterface $logger, array $allow, bool $lazy = true, Event $event = null)
    {
        $this->name = $name;
        $this->logger = $logger;
        $this->allow = $allow;
        $this->lazy = $lazy;
        $this->event = $event;
    }

    /**
     * Note: 记录日志信息
     * Date: 2022-12-08
     * Time: 11:03
     * @param mixed $msg 信息
     * @param string $type 级别
     * @param array $context 上下文
     * @param bool $lazy 是否延迟写入
     * @return $this
     */
    public function record($msg, string $type = 'info', array $context = [], bool $lazy = true)
    {
        if ($this->close) {
            return $this;
        }
        if (!empty($this->allow) && !in_array($type, $this->allow)) {
            return $this;
        }

        if (is_string($msg) && !empty($context)) {
            $replace = [];
            foreach ($context as $key => $val) {
                $replace['{' . $key . '}'] = $val;
            }
            $msg = strtr($msg, $replace);
        }

        if (!empty($msg) || $msg === 0) {
            $this->log[$type][] = $msg;
            if ($this->event) {
                $this->event->trigger(new LogRecord($type, $msg));
            }
        }

        if (!$this->lazy || !$lazy) {
            $this->save();
        }

        return $this;
    }

    /**
     * Note: 实时写入日志信息
     * Date: 2023-08-29
     * Time: 16:33
     * @param mixed $msg 日志信息
     * @param string $type 日志类型
     * @param array $context 日志上下文
     * @return $this
     */
    public function write($msg, string $type = 'info', array $context = [])
    {
        return $this->record($msg, $type, $context, false);
    }

    /**
     * Note: 获取日志信息
     * Date: 2023-08-29
     * Time: 16:35
     * @return array
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * Note: 保存日志
     * Date: 2022-12-08
     * Time: 17:21
     * @return bool
     */
    public function save()
    {
        $log = $this->log;
        if ($this->event) {
            $event = new LogWrite($this->name, $log);
            $this->event->trigger($event);
        }

        if ($this->logger->save($log)) {
            $this->clear();
            return true;
        }

        return false;
    }

    /**
     * Note: 清空日志信息
     * Date: 2022-12-08
     * Time: 17:27
     */
    public function clear()
    {
        $this->log = [];
    }

    /**
     * Note: 关闭通道
     * Date: 2023-08-26
     * Time: 17:34
     * @return void
     */
    public function close()
    {
        $this->clear();

        $this->close = true;
    }
}