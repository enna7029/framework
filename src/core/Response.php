<?php
declare(strict_types=1);

namespace Enna\Framework;

abstract class Response
{
    /**
     * 数据
     * @var mixed
     */
    protected $data;

    /**
     * 输出内容
     * @var null
     */
    protected $content = null;

    /**
     * 状态码
     * @var int
     */
    protected $code = 200;

    /**
     * 当前ContentType
     * @var string
     */
    protected $contentType = 'text/html';

    /**
     * 字符集
     * @var string
     */
    protected $charset = 'utf-8';

    /**
     * 响应头
     * @var array
     */
    protected $header = [];

    /**
     * Cookie对象
     * @var Cookie
     */
    protected $cookie;

    /**
     * session对象
     * @var Session
     */
    protected $session;

    /**
     * 是否允许请求缓存
     * @var bool
     */
    protected $allowCache;

    /**
     * 输出参数
     * @var array
     */
    protected $options = [];

    /**
     * Note: 初始化
     * Date: 2022-10-08
     * Time: 17:31
     * @param mixed $data 输出数据
     * @param int $code 状态码
     */
    protected function init($data = '', int $code = 200)
    {
        $this->data($data);
        $this->code = $code;

        $this->contentType($this->contentType, $this->charset);
    }

    /**
     * Note: 创建Response对象
     * Date: 2022-09-30
     * Time: 10:56
     * @param mixed $data 输出信息
     * @param string $type 输出类型
     * @param int $code 状态码
     * @return Response
     */
    public static function create($data = '', string $type = 'html', int $code = 200): Response
    {
        $class = '\\Enna\\Framework\\Response\\' . ucfirst(strtolower($type));

        return Container::getInstance()->invokeClass($class, [$data, $code]);
    }

    /**
     * Note: 输出数据设置
     * Date: 2022-10-08
     * Time: 17:32
     * @param mixed $data 输出数据
     * @return $this
     */
    protected function data($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Note: 输出类型
     * Date: 2022-10-08
     * Time: 17:37
     * @param string $contentType 类型
     * @param string $charset 编码
     * @return $this
     */
    protected function contentType(string $contentType, string $charset)
    {
        $this->header['Content-Type'] = $contentType . '; charset=' . $charset;

        return $this;
    }

    /**
     * Note: 设置响应头
     * Date: 2022-09-30
     * Time: 11:07
     * @param array $header header头
     * @return $this
     */
    public function header(array $header = [])
    {
        $this->header = array_merge($this->header, $header);

        return $this;
    }

    /**
     * Note: 设置编码
     * Date: 2022-10-26
     * Time: 14:24
     * @param int $code
     * @return $this
     */
    public function code(int $code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Note: 设置cookie
     * Date: 2023-02-28
     * Time: 11:00
     * @param string $name cookie名称
     * @param string $value cookie值
     * @param mixed $option 选项
     * @return $this
     */
    public function cookie(string $name, string $value, $option = null)
    {
        $this->cookie->set();

        return $this;
    }

    /**
     * Note: 设置Session对象
     * Date: 2023-03-08
     * Time: 15:02
     * @param Session $session
     * @return $this
     */
    public function setSession(Session $session)
    {
        $this->session = $session;
        return $this;
    }

    /**
     * Note: 发送数据到客户端
     * Date: 2022-10-08
     * Time: 17:42
     * @return void
     */
    public function send()
    {
        $data = $this->getContent();

        if (!headers_sent()) {
            if (!empty($this->header)) {
                http_response_code($this->code);
                foreach ($this->header as $name => $val) {
                    header($name . (!is_null($val) ? ':' . $val : ''));
                }
            }

            if ($this->cookie) {
                $this->cookie->save();
            }
        }

        $this->sendData($data);

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    /**
     * Note: 获取输出数据
     * Date: 2022-10-08
     * Time: 17:43
     * @return string
     */
    public function getContent()
    {
        if ($this->content == null) {
            $content = $this->output($this->data);
            if ($content !== null && !is_string($content) && !is_numeric($content) && !is_callable([$content, '__toString'])) {
                throw new \InvalidArgumentException(sprintf('variable type error： %s', gettype($content)));
            }
            $this->content = (string)$content;
        }

        return $this->content;
    }

    /**
     * Note: 要处理的数据
     * Date: 2022-10-26
     * Time: 14:36
     * @param mixed $data 数据
     * @return string
     */
    public function output($data)
    {
        return $data;
    }

    /**
     * Note: 输出数据
     * Date: 2022-10-08
     * Time: 18:40
     * @param string $data
     * @return void
     */
    protected function sendData(string $data)
    {
        echo $data;
    }

    /**
     * Note: 是否允许缓存
     * Date: 2023-03-13
     * Time: 16:37
     * @param bool $cache 输出缓存
     * @return $this
     */
    public function allowCache(bool $cache)
    {
        $this->allowCache = $cache;

        return $this;
    }

    /**
     * Note: 设置header修改时间
     * Date: 2023-03-13
     * Time: 16:40
     * @param string $time GMT格式时间
     * @return $this
     */
    public function lastModified(string $time)
    {
        $this->header['Last-Modified'] = $time;

        return $this;
    }

    /**
     * Note: 页面缓存控制
     * Date: 2023-03-13
     * Time: 16:43
     * @param string $cache 状态码
     * @return $this
     */
    public function cacheControl(string $cache)
    {
        $this->header['Cache-control'] = $cache;

        return $this;
    }
}