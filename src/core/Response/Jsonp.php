<?php
declare(strict_types=1);

namespace Enna\Framework\Response;

use Enna\Framework\Cookie;
use Enna\Framework\Request;
use Enna\Framework\Response;

/**
 * JSONP格式响应
 * Class Jsonp
 * @package Enna\Framework\Response
 */
class Jsonp extends Response
{
    protected $options = [
        'var_jsonp_handler' => 'callback',
        'default_jsonp_handler' => 'jsonReturn',
        'json_encode_param' => JSON_UNESCAPED_UNICODE,
    ];

    protected $contentType = 'application/javascript';

    protected $request;

    public function __construct(Cookie $cookie, Request $request, $data = '', int $code = 200)
    {
        $this->init($data, $code);

        $this->cookie = $cookie;
        $this->request = $request;
    }

    /**
     * Note: 处理数据
     * Date: 2023-08-21
     * Time: 15:33
     * @param mixed $data
     * @return string|void
     * @throws \Exception
     */
    protected function output($data)
    {
        try {
            $varJsonpHandler = $this->request->param($this->options['var_jsonp_handler'], "");
            $handler = !empty($varJsonpHandler) ? $varJsonpHandler : $this->options['default_jsonp_handler'];

            $data = json_encode($data, $this->options['json_encode_param']);
            if ($data === false) {
                throw new \InvalidArgumentException(json_last_error_msg());
            }

            $data = $handler . '(' . $data . ');';

            return $data;
        } catch (\Exception $e) {
            if ($e->getPrevious()) {
                throw $e->getPrevious();
            }

            throw $e;
        }
    }
}