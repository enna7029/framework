<?php

namespace Enna\Framework\Exception;

use Throwable;
use RuntimeException;
use Psr\Container\NotFoundExceptionInterface;

class FuncNotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
    public $func;

    public function __construct(string $message, string $func, Throwable $previous = null)
    {
        $this->message = $message;
        $this->func = $func;

        parent::__construct($message, 0, $previous);
    }

    public function getFunc()
    {
        return $this->func;
    }
}