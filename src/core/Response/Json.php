<?php
declare(strict_types=1);

namespace Enna\Framework\Response;

use Enna\Framework\Cookie;
use Enna\Framework\Response;

/**
 * JSON格式响应
 * Class Json
 * @package Enna\Framework\Response
 */
class Json extends Response
{
    /**
     * 输出参数
     * @var array
     */
    protected $options = [
        'json_encode_param' => JSON_UNESCAPED_UNICODE,
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
     * @param mixed $data 要处理的数据
     * @return string
     * @throws \Exception
     */
    protected function output($data)
    {
        try {
            $data = json_encode($data, $this->options['json_encode_param']);

            if ($data) {
                throw new \InvalidArgumentException(json_last_error_msg());
            }

            return $data;
        } catch (\Exception $e) {
            if ($e->getPrevious()) {
                throw $e->getPrevious();
            }
            throw $e;
        }
    }
}