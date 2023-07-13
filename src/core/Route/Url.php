<?php
declare(strict_types=1);

namespace Enna\Framework\Route;

use Enna\Framework\App;
use Enna\Framework\Route;

/**
 * 路由地址生成
 * Class Url
 * @package Enna\Framework\Route
 */
class Url
{
    /**
     * 路由对象
     * @var Route
     */
    protected $route;

    /**
     * 应用实例对象
     * @var App
     */
    protected $app;

    /**
     * 路由URL
     * @var string
     */
    protected $url;

    /**
     * URL根地址
     * @var string
     */
    protected $root;

    /**
     * URL变量
     * @var array
     */
    protected $vars = [];

    /**
     * HTTPS
     * @var string|bool
     */
    protected $https;

    /**
     * URL后缀
     * @var bool
     */
    protected $suffix = true;

    /**
     * URL域名
     * @var bool
     */
    protected $domain = false;

    /**
     * Url constructor.
     * @param Route $route 路由对象
     * @param App $app 应用对象
     * @param string $url URL地址
     * @param array $vars 参数
     */
    public function __construct(Route $route, App $app, string $url = '', array $vars = [])
    {
        $this->route = $route;
        $this->app = $app;
        $this->url = $url;
        $this->vars = $vars;
    }

    /**
     * Note: 设置URL参数
     * Date: 2023-07-10
     * Time: 17:38
     * @param array $vars URL参数
     * @return $this
     */
    public function vars(array $vars = [])
    {
        $this->vars = $vars;

        return $this;
    }

    /**
     * Note: 设置URL后缀
     * Date: 2023-07-10
     * Time: 17:39
     * @param string|bool $suffix URL后缀
     * @return $this
     */
    public function suffix($suffix)
    {
        $this->suffix = $suffix;

        return $this;
    }

    /**
     * Note: 设置URL域名(或者子域名)
     * Date: 2023-07-10
     * Time: 17:40
     * @param string|bool $domain URL域名
     * @return $this
     */
    public function domain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Note: 设置URL根地址
     * Date: 2023-07-10
     * Time: 17:43
     * @param string $root
     * @return $this
     */
    public function root(string $root)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * Note: 设置是否使用HTTPS
     * Date: 2023-07-10
     * Time: 17:43
     * @param bool $https
     * @return $this
     */
    public function https(bool $https = true)
    {
        $this->https = $https;

        return $this;
    }

    /**
     * Note: 生成URL地址
     * Date: 2023-07-13
     * Time: 15:37
     * @return string
     */
    public function build()
    {

    }

    public function __toString()
    {
        return $this->build();
    }

    public function __debugInfo()
    {
        return [
            'url' => $this->url,
            'vars' => $this->vars,
            'suffix' => $this->suffix,
            'domain' => $this->domain,
        ];
    }
}