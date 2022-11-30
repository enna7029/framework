<?php
declare (strict_types=1);

namespace Enna\Framework\Event;

class LogWrite
{
    public $channel;

    public $log;

    public function __construct($channel, $log)
    {
        $this->channel = $channel;
        $this->log = $log;
    }
}