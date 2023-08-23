<?php
declare(strict_types=1);

namespace Enna\Framework\Response;

use Enna\Framework\Response;
use Enna\Framework\Cookie;

/**
 * HTML格式响应
 * Class Html
 * @package Enna\Framework\Response
 */
class Html extends Response
{
    /**
     * 设置header头Content-Type的mime类型
     * @var string
     */
    protected $contentType = 'text/html';

    public function __construct(Cookie $cookie, $data = '', int $code = 200)
    {
        $this->init($data, $code);
        $this->cookie = $cookie;
    }
}