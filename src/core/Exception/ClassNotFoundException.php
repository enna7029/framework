<?php

namespace Enna\Framework\Exception;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Throwable;

class ClassNotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
    public $class;

    public function __construct(string $message, string $class, Throwable $previous = null)
    {
        $this->message = $message;
        $this->class = $class;

        parent::__construct($message, 0, $previous);
    }

    public function getClass()
    {
        return $this->class;
    }
}