<?php
declare (strict_types=1);

namespace Enna\Framework\Event;

class LogRecord
{
    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $message;

    public function __construct($type, $message)
    {
        $this->type = $type;
        $this->message = $message;
    }
}