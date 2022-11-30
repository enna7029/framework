<?php
declare(strict_types=1);

namespace Enna\Framework\Exception;

use Exception;

class HttpException extends \RuntimeException
{
    private $statusCode;

    private $headers;

    public function __construct(int $statusCode, string $message = '', Exception $previous = null, array $headers = [], $code = 0)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;

        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getHeaders()
    {
        return $this->headers;
    }
}