<?php
declare(strict_types=1);

namespace Enna\Framework\Exception;

use Throwable;

class ValidateException extends \RuntimeException
{
    protected $error;

    public function __construct($error)
    {
        $this->error = $error;
        $this->message = is_array($error) ? implode(PHP_EOL, $error) : $error;
    }

    public function getError()
    {
        return $this->error;
    }
}