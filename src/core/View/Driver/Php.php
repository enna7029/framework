<?php
declare(strict_types=1);

namespace Enna\Framework\View\Driver;

use Enna\Framework\App;
use Enna\Framework\Helper\Str;
use RuntimeException;

/**
 * PHP原生模板驱动
 * Class Php
 * @package Enna\Framework\View\Driver
 */
class Php implements TemplateHandlerInterface
{
    /**
     * @var App
     */
    protected $app;

    /**
     * 模板文件
     * @var string
     */
    protected $template;

    /**
     * 渲染的内容
     * @var mixed
     */
    protected $content;

    /**
     * 配置
     * @var array
     */
    protected $config = [
        //默认模板渲染规则 1 解析为小写+下划线 2 全部转换小写 3 保持操作方法
        'auto_rule' => 1,
        //视图目录名
        'view_dir_name' => 'view',
        //应用模板路径
        'view_path' => '',
        //模板文件后缀
        'view_suffix' => 'php',
        //模板文件名分隔符
        'view_depr' => DIRECTORY_SEPARATOR,
    ];

    public function __construct(App $app, array $config = [])
    {
        $this->app = $app;
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Note: 检测是否存在模板文件
     * Date: 2023-11-30
     * Time: 11:46
     * @param string $template 模板文件或者模板规则
     * @return bool
     */
    public function exists(string $template)
    {
        if (phpinfo($template, PATHINFO_EXTENSION) == '') {
            $template = $this->parseTemplate($template);
        }

        return is_file($template);
    }

    /**
     * Note: 渲染模板文件
     * Date: 2023-12-01
     * Time: 10:45
     * @param string $template 模板文件
     * @param array $data 模板变量
     * @return void
     */
    public function fetch(string $template, array $data = [])
    {
        if (phpinfo($template, PATHINFO_EXTENSION) == '') {
            $template = $this->parseTemplate($template);
        }

        if (!is_file($template)) {
            throw new RuntimeException('template not exists:' . $template);
        }

        $this->template = $template;

        extract($data, EXTR_OVERWRITE);

        include $this->template;
    }

    /**
     * Note: 渲染模板内容
     * Date: 2023-12-01
     * Time: 11:27
     * @param string $content 模板内容
     * @param array $data 模板变量
     * @return void
     */
    public function display(string $content, array $data = [])
    {
        $this->content = $content;

        extract($data, EXTR_OVERWRITE);

        eval('?>' . $this->content);
    }

    /**
     * Note: 定位模板文件
     * Date: 2023-11-30
     * Time: 18:16
     * @param string $template 模板文件规则
     * @return string
     */
    private function parseTemplate(string $template)
    {
        $request = $this->app->request;

        if (strpos($template, '@')) {
            [$app, $template] = explode('@', $template);
        }

        if ($this->config['view_path'] && !isset($app)) {
            $path = $this->config['view_path'];
        } else {
            $appName = isset($app) ? $app : $this->app->http->getName();
            $view = $this->config['view_dir_name'];

            if (is_dir($this->app->getAppPath() . $view)) {
                $path = isset($app) ? $this->app->getBasePath() . ($appName ? $appName . DIRECTORY_SEPARATOR : '') . $view . DIRECTORY_SEPARATOR : $this->app->getAppPath() . $view . DIRECTORY_SEPARATOR;
            } else {
                $path = $this->app->getRootPath() . $view . DIRECTORY_SEPARATOR . ($appName ? $appName . DIRECTORY_SEPARATOR : '');
            }
        }

        $depr = $this->config['view_depr'];

        if (strpos($template, '/') !== 0) {
            $template = str_replace(['/', ':'], $depr, $template);

            $controller = $request->controller();
            if (strpos($controller, '.')) {
                $pos = strrpos($controller, '.');
                $controller = substr($controller, 0, $pos) . '.' . Str::snake($controller, $pos + 1);
            } else {
                $controller = Str::snake($controller);
            }

            if ($controller) {
                if ($template == '') {
                    if ($this->config['auto_rule'] == 2) {
                        $template = $request->action(true);
                    } elseif ($this->config['auto_rule'] == 3) {
                        $template = $request->action();
                    } else {
                        $template = Str::snake($request->action());
                    }
                    $template = str_replace('.', DIRECTORY_SEPARATOR, $controller) . $depr . $template;
                } elseif (strpos($template, $depr) === false) {
                    $template = str_replace('.', DIRECTORY_SEPARATOR, $controller) . $depr . $template;
                }
            }
        } else {
            $template = str_replace(['/', ':'], $depr, substr($template, 1));
        }

        return $path . ltrim($template, '/') . '.' . ltrim($this->config['view_suffix'], '.');
    }

    /**
     * Note: 配置模板引擎
     * Date: 2023-11-30
     * Time: 11:48
     * @param array $config 参数
     * @return void
     */
    public function config(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Note: 获取模板引擎配置
     * Date: 2023-11-30
     * Time: 11:48
     * @param string $name 参数名
     * @return mixed|null
     */
    public function getConfig(string $name)
    {
        return $this->config[$name] ?? null;
    }
}