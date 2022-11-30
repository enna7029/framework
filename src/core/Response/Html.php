<?php
declare(strict_types=1);

namespace Enna\Framework\Response;

use Enna\Framework\Response;
use Enna\Framework\Cookie;

class Html extends Response
{
    protected $contentType = 'text/html';

    public function __construct(Cookie $cookie, $data = '', int $code = 200)
    {
        $this->init($data, $code);
        $this->cookie = $cookie;
    }
}