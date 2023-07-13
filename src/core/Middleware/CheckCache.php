<?php
declare(strict_types=1);

namespace Enna\Framework\Middleware;

use Closure;
use Enna\Framework\Cache;
use Enna\Framework\Config;

class CheckCache
{
    /**
     * 缓存对象
     * @var Cache
     */
    protected $cache;

    /**
     * 配置参数
     * @var array
     */
    protected $config = [];

    public function __construct(Cache $cache, Config $config)
    {
        $this->cache = $cache;
        $this->config = array_merge($this->config, $config->set('route'));
    }

    public function handle($request, Closure $next, $cache = null)
    {

    }
}