<?php
declare(strict_types=1);

namespace Enna\Framework;

use Enna\Framework\View as BaseView;

class View extends Response
{
    /**
     * 输出参数
     * @var array
     */
    protected $options = [];

    /**
     * 输出变量
     * @var array
     */
    protected $vars = [];

    /**
     * 输出过滤
     * @var mixed
     */
    protected $filter;

    /**
     * 输出type
     * @var string
     */
    protected $contentType = 'text/html';

    /**
     * View对象
     * @var BaseView
     */
    protected $view;

    /**
     * 是否内容渲染
     * @var bool
     */
    protected $isContent = false;

    public function __construct(Cookie $cookie, BaseView $view, $data = '', int $code = 200)
    {
        $this->init($data, $code);

        $this->cookie = $cookie;
        $this->view = $view;
    }

    /**
     * Note: 设置是否为内容渲染
     * Date: 2023-11-29
     * Time: 11:26
     * @param bool $content
     * return $this
     */
    public function isContent(bool $content = true)
    {
        $this->isContent = $content;
    }

    /**
     * Note: 处理数据
     * Date: 2023-11-29
     * Time: 11:26
     * @param mixed $data 要处理的数据
     * @return string|void
     */
    protected function output($data)
    {
        $this->view->filter($this->filter);

        return $this->isContent ? $this->view->display($data, $this->vars) : $this->view->fetch($data, $this->vars);
    }

    /**
     * Note: 获取识图变量
     * Date: 2023-11-29
     * Time: 11:33
     * @param string|null $name
     * @return array|mixed|null
     */
    public function getVars(string $name = null)
    {
        if (is_null($name)) {
            return $this->vars;
        } else {
            return $this->vars[$name] ?? null;
        }
    }

    /**
     * Note: 模板变量赋值
     * Date: 2023-11-29
     * Time: 11:34
     * @param string|array $name 模板变量
     * @param mixed $value 变量值
     * @return $this
     */
    public function assign($name, $value = null)
    {
        if (is_array($name)) {
            $this->vars = array_merge($this->vars, $value);
        } else {
            $this->vars[$name] = $value;
        }

        return $this;
    }

    /**
     * Note: 识图内容过滤
     * Date: 2023-11-29
     * Time: 11:37
     * @param callable|null $filter
     * @return $this
     */
    public function filter(callable $filter = null)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Note: 检查模板是否存在
     * Date: 2023-11-29
     * Time: 11:21
     * @param string $name
     * @return mixed
     */
    public function exists(string $name)
    {
        return $this->view->exists();
    }
}