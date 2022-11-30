<?php
declare(strict_types=1);

namespace Enna\Framework\Response;

use Enna\Framework\Response;

class Redirect extends Response
{

    public function __construct($data = '', int $code = 302)
    {
        $this->init((string)$data, $code);
    }

    /**
     * Note: 处理数据
     * Date: 2022-10-26
     * Time: 14:31
     * @param mixed $data 数据
     * @return string
     */
    protected function output($data)
    {
        $this->header['Location'] = $data;

        return '';
    }
}