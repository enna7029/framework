<?php
declare(strict_types=1);

use Enna\Framework\Cookie;
use Enna\Framework\Response;

class Json extends Response
{
    /**
     * 输出参数
     * @var array
     */
    protected $options = [
        'json_encode_param' => JSON_UNESCAPED_UNICODEN,
    ];

    protected $contentType = 'application/json';

    public function __construct(Cookie $cookie, $data = '', int $code = 200)
    {
        $this->init($data, $code);
        $this->cookie = $cookie;
    }

    /**
     * Note: 处理数据
     * Date: 2023-03-13
     * Time: 18:28
     * @param mixed $data
     * @return string
     */
    protected function output($data)
    {
        $data = json_encode($data, $this->options['json_encode_param']);

        if ($data === false) {
            throw new \InvalidArgumentException(json_last_error_msg());
        }

        return $data;
    }
}