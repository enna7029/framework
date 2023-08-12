<?php
declare(strict_types=1);

namespace Enna\Framework\Exception;

use Enna\Framework\Response;

/**
 * HTTP响应异常
 * Class HttpResponseException
 * @package Enna\Framework\Exception
 */
class HttpResponseException extends \RuntimeException
{
    protected $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function getResponse()
    {
        return $this->response;
    }
}