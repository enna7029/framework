<?php
declare (strict_types=1);

namespace Enna\Framework\Event;

class LogRecord
{
    /**
     * 日志级别
     * @var string
     */
    public $type;

    /**
     * 日志信息
     * @var string
     */
    public $message;

    public function __construct($type, $message)
    {
        $this->type = $type;
        $this->message = $message;
    }
}