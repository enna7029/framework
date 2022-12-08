<?php
declare (strict_types=1);

namespace Enna\Framework\Event;

class LogWrite
{
    /**
     * 渠道
     * @var string
     */
    public $channel;

    /**
     * 日志信息
     * @var array
     */
    public $log;

    public function __construct($channel, $log)
    {
        $this->channel = $channel;
        $this->log = $log;
    }
}