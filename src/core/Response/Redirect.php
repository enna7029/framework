<?php
declare(strict_types=1);

namespace Enna\Framework\Response;

use Enna\Framework\Cookie;
use Enna\Framework\Pipeline;
use Enna\Framework\Request;
use Enna\Framework\Session;
use Enna\Framework\Response;

class Redirect extends Response
{

    /**
     * 请求实例
     * @var Request
     */
    protected $request;

    public function __construct(Cookie $cookie, Request $request, Session $session, $data = '', int $code = 302)
    {
        $this->init((string)$data, $code);

        $this->cookie = $cookie;
        $this->request = $request;
        $this->session = $session;

        $this->cacheControl('no-cache,must-revalidate');
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

    /**
     * Note: 重定向传值(通过Session)
     * Date: 2023-03-13
     * Time: 16:50
     * @param $name
     * @param null $value
     */
    public function with($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $key => $val) {
                $this->session->flash($key, $val);
            }
        } else {
            $this->session->flash($name, $value);
        }

        return $this;
    }

    /**
     * Note: 记住当前的url
     * Date: 2023-03-13
     * Time: 16:57
     * @return $this
     */
    public function remeber()
    {
        $this->session->set('redirect_url', $this->request->url());

        return $this;
    }

    /**
     * Note: 跳转到上次记住的url
     * Date: 2023-03-13
     * Time: 16:58
     * @return $this
     */
    public function restore()
    {
        if ($this->session->has('redirect_url')) {
            $this->data = $this->session->get('redirect_url');
            $this->session->delete('redirect_url');
        }

        return $this;
    }

}