<?php
declare(strict_types=1);

namespace Enna\Framework;

class View extends Manager
{
    protected $namespace = '\\Enna\\View\\Driver\\';

    /**
     * 模板变量
     * @var array
     */
    protected $data = [];

    /**
     * 内容过滤
     * @var mixed
     */
    protected $filter;

    /**
     * Note: 获取模板引擎
     * Date: 2023-11-29
     * Time: 17:53
     * @param string|null $type 模板引擎类型
     * @return mixed
     */
    public function engine(string $type = null)
    {
        return $this->driver($type);
    }

    /**
     * Note: 获取默认驱动
     * Date: 2023-11-29
     * Time: 18:02
     * @return array|mixed|null
     */
    public function getDefaultDriver()
    {
        return $this->app->config->get('view.type', 'php');
    }

    /**
     * Note: 解析配置
     * Date: 2023-11-29
     * Time: 18:04
     * @param string $name
     * @return array|mixed|string|null
     */
    protected function resolveConfig(string $name)
    {
        $config = $this->app->config->get('view', []);

        return $config;
    }

    /**
     * Note: 模板变量赋值
     * Date: 2023-11-30
     * Time: 9:26
     * @param string|array $name 模板变量
     * @param mixed $value 变量值
     * @return $this
     */
    public function assign($name, $value = null)
    {
        if (is_array($name)) {
            $this->data = array_merge($this->data, $name);
        } else {
            $this->data[$name] = $value;
        }

        return $this;
    }

    /**
     * Note: 识图过滤
     * Date: 2023-11-30
     * Time: 9:27
     * @param callable|null $filter
     * @return $this
     */
    public function filter(callable $filter = null)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Note: 解析和获取模板内容,用于输出
     * Date: 2023-11-30
     * Time: 9:28
     * @param string $template 模板文件名或内容
     * @param array $vars 模板变量
     * @return string
     * @throws \Exception
     */
    public function fetch(string $template = '', array $vars = [])
    {
        return $this->getContent(function () use ($vars, $template) {
            $this->engine()->fetch($template, array_merge($this->data, $vars));
        });
    }

    /**
     * Note: 渲染内容输出
     * Date: 2023-11-30
     * Time: 9:34
     * @param string $content 内容
     * @param array $vars 模板变量
     * @return string
     * @throws \Exception
     */
    public function display(string $content, array $vars = [])
    {
        return $this->getContent(function () use ($vars, $content) {
            $this->engine()->display($content, array_merge($this->data, $vars));
        });
    }

    /**
     * Note: 获取模板引擎渲染内容
     * Date: 2023-11-30
     * Time: 9:34
     * @param callable $callback 闭包
     * @return string
     * @throws \Exception
     */
    protected function getContent($callback)
    {
        ob_start();
        if (PHP_VERSION > 8.0) {
            ob_implicit_flush(false);
        } else {
            ob_implicit_flush(0);
        }

        try {
            $callback();
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }

        $content = ob_get_clean();

        if ($this->filter) {
            $content = call_user_func_array($this->filter, [$content]);
        }
    }

    /**
     * Note: 模板变量赋值
     * Date: 2023-11-29
     * Time: 18:06
     * @param string $name 变量名
     * @param mixed $value 变量值
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * Note: 取得模板显示变量的值
     * Date: 2023-11-29
     * Time: 18:06
     * @param string $name 模板变量
     * @return mixed
     */
    public function __get($name)
    {
        return $this->data[$name];
    }

    /**
     * Note: 检测模板变量是否设置
     * Date: 2023-11-29
     * Time: 18:06
     * @param string $name 模板变量名
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }
}