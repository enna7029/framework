<?php
declare(strict_types=1);

namespace Enna\Framework\Exception;

class RouteNotFoundException extends HttpException
{
    public function __construct()
    {
        parent::__construct(404, 'Route Not Found');
    }
}