<?php
declare(strict_types=1);

namespace Enna\Framework\Middleware;

use Closure;
use Enna\Framework\Cache;
use Enna\Framework\Config;
use Enna\Framework\Request;
use Enna\Framework\Response;

class CheckRequestCache
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
    protected $config = [
        //是否开启请求缓存
        'request_cache_key' => true,
        //请求缓存有效期
        'request_cache_expire' => null,
        //请求缓存排除规则
        'request_cache_except' => [],
        //请求缓存的tag
        'request_cache_tag' => '',
    ];

    public function __construct(Cache $cache, Config $config)
    {
        $this->cache = $cache;
        $this->config = array_merge($this->config, $config->get('route'));
    }

    public function handle(Request $request, Closure $next, $cache = null)
    {

        if ($request->isGet() && $cache !== false) {
            if ($this->config['request_cache_key'] === false) {
                $cache = false;
            }

            $cache = $cache ?? $this->getRequestCache($request);
            if ($cache) {
                if (is_array($cache)) {
                    [$key, $expire, $tag] = array_pad($cache, 3, null);
                } else {
                    $key = md5($request->url(true));
                    $expire = $cache;
                    $tag = null;
                }

                $key = $this->parseCacheKey($request, $key);

                if (strtotime($request->server('HTTP_IF_MODIFIED_SINCE', '')) + $expire > $request->server('REQUEST_TIME')) {
                    return Response::create()->code(304);
                } elseif ($data = $this->cache->get($key)) {
                    [$content, $header, $time] = $data;
                    if ($expire == null || $time + $expire > $request->server('REQUEST_TIME')) {
                        return Response::create($content)->header($header);
                    }
                }
            }
        }

        $response = $next($request);

        if (isset($key) && $response->getCode() == 200 && $response->isAllowCache()) {
            $header = $response->getHeader();
            $header['Cache-Control'] = 'max-age=' . $expire . ',must-revalidate';
            $header['Last-Modified'] = gmdate('D,d M Y H:i:s') . ' GMT';
            $header['Expires'] = gmdate('D,d M Y H:i:s', time() + $expire) . ' GMT';

            $this->cache->tag($tag)->set($key, [$response->getContent(), $header, time()], $expire);
        }

        return $response;
    }

    /**
     * Note: 读取当前地址的请求缓存信息
     * Date: 2023-08-03
     * Time: 17:39
     * @param Request $request 请求对象
     * @return array
     */
    protected function getRequestCache($request)
    {
        $key = $this->config['request_cache_key'];
        $expire = $this->config['request_cache_expire'];
        $tag = $this->config['request_cache_tag'];
        $except = $this->config['request_cache_except'];

        foreach ($except as $rule) {
            if (stripos($request->url(), $rule) === 0) {
                return;
            }
        }

        return [$key, $expire, $tag];
    }

    /**
     * Note: 获取当前的缓存key
     * Date: 2023-08-03
     * Time: 17:43
     * @param Request $request 请求对象
     * @param mixed $key 缓存key
     * @return string|null
     */
    protected function parseCacheKey($request, $key)
    {
        if ($key instanceof Closure) {
            $key = call_user_func($key, $request);
        }

        if ($key === false) {
            return;
        }

        if ($key === true) {
            $key = '__URL__';
        } elseif (strpos($key, '|')) {
            [$key, $func] = explode('|', $key);
        }

        if (strpos($key, '__') !== false) {
            $key = str_replace(['__CONTROLLER__', '__ACTION__', '__URL__'], [$request->controller(), $request->action(), md5($request->url(true))], $key);
        }

        if (isset($func)) {
            $key = $func($key);
        }

        return $key;
    }
}