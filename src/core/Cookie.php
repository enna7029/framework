<?php
declare(strict_types=1);

namespace Enna\Framework;

class Cookie
{
    /**
     * 配置参数
     * @var array
     */
    protected $config = [
        'expire' => 0, //过期时间
        'path' => '/', //保存路径
        'domain' => '', //有效域名
        'secure' => false, //启用安全传输
        'httponly' => false, //httpOnly设置
    ];

    /**
     * Cookie写入数据
     * @var array
     */
    protected $cookie = [];

    /**
     * Request对象
     * @var Request
     */
    protected $request;

    public function __construct(Request $request, array $config = [])
    {
        $this->request = $request;
        $this->config = array_merge($this->config, array_change_key_case($config));
    }

    public static function __make(Request $request, Config $config)
    {
        return new static($request, $config->get('cookie'));
    }

    /**
     * Note: 保存cookie
     * Date: 2022-10-08
     * Time: 18:28
     * @return void
     */
    public function save()
    {
        foreach ($this->cookie as $name => $val) {
            [$value, $expire, $option] = $val;

            $this->saveCookie(
                $name,
                $value,
                $expire,
                $option['path'],
                $option['domain'],
                $option['secure'] ? true : false,
                $option['httponly'] ? true : false,
            );
        }
    }

    /**
     * Note: 保存cookie
     * Date: 2022-10-08
     * Time: 18:31
     * @param string $name cookie名称
     * @param string $value cookie值
     * @param int $expire cookie过期时间
     * @param string $path cookie路径
     * @param string $domain 有效域名
     * @param bool $secure 仅通过httponly访问
     * @param bool $httponly
     */
    protected function saveCookie(string $name, string $value, int $expire, string $path, string $domain, bool $secure, bool $httponly)
    {
        if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
            setcookie($name, $value, [
                'expires' => $expire,
                'path' => $path,
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => $httponly,
            ]);
        } else {
            setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        }
    }
}