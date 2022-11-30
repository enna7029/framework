<?php
declare(strict_types=1);

namespace Enna\Framework\Route;

use Enna\Framework\App;
use Enna\Framework\Route;

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
     * URL变量
     * @var array
     */
    protected $vars = [];

    public function __construct(Route $route, App $app, string $url = '', array $vars = [])
    {
        $this->route = $route;
        $this->app = $app;
        $this->url = $url;
        $this->vars = $vars;
    }
}