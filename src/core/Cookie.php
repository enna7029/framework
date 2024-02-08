<?php
declare(strict_types=1);

namespace Enna\Framework;

use \DateTimeInterface;

/**
 * Cookie管理类
 * Class Cookie
 * @package Enna\Framework
 */
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
        'samesite' => '', //支持 'strict' 'lax'
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
     * Note: 是否存在Cookie数据
     * Date: 2023-02-28
     * Time: 11:26
     * @param string $name cookie名称
     * @return bool
     */
    public function has(string $name)
    {
        return $this->request->has($name, 'cookie');
    }

    /**
     * Note: 获取 Cookie
     * Date: 2023-02-28
     * Time: 10:58
     * @param string $name 数据名称
     * @param null $default 默认值
     * @return mixed
     */
    public function get(string $name = '', $default = null)
    {
        return $this->request->cookie($name, $default);
    }

    /**
     * Note: 永久保存Cookie数据
     * Date: 2023-02-28
     * Time: 11:29
     * @param string $name cookie名称
     * @param string $value cookie值
     * @param mixed $option 可选选项
     * @return void
     */
    public function forever(string $name, string $value = '', $option = null)
    {
        if (is_null($option) || is_numeric($option)) {
            $option = [];
        }

        $option['expire'] = 315360000;

        $this->set($name, $value, $option);
    }

    /**
     * Note: Cookie删除
     * Date: 2023-02-28
     * Time: 11:32
     * @param string $name cookie名称
     * @return void
     */
    public function delete(string $name)
    {
        $this->setCookie($name, '', time() - 3600, $this->config);
    }

    /**
     * Note: 设置 Cookie
     * Date: 2023-02-27
     * Time: 18:59
     * @param string $name cookie名称
     * @param string $value cookie值
     * @param mixed $option 可选参数
     * @return void
     */
    public function set(string $name, string $value, $option = null)
    {
        if (!is_null($option)) {
            if (is_numeric($option) || $option instanceof DateTimeInterface) {
                $option = ['expire' => $option];
            }
            $config = array_merge($this->config, array_change_key_case($option));
        } else {
            $config = $this->config;
        }

        if ($config['expire'] instanceof DateTimeInterface) {
            $expire = $config['expire']->getTimestamp();
        } else {
            $expire = !empty($config['expire']) ? time() + intval($config['expire']) : 0;
        }

        $this->setCookie($name, $value, $expire, $config);
    }

    /**
     * Note: 设置 Cookie
     * Date: 2023-02-27
     * Time: 18:53
     * @param string $name cookie名称
     * @param string $value cookie值
     * @param int $expire 有效期
     * @param array $option 可选参数
     * @return void
     */
    public function setCookie(string $name, string $value, int $expire, array $option = [])
    {
        $this->cookie[$name] = [$value, $expire, $option];
    }

    /**
     * Note: 获取Cookie保存数据
     * Date: 2023-02-27
     * Time: 18:52
     * @return array
     */
    public function getCookie()
    {
        return $this->cookie;
    }

    /**
     * Note: 保存Cookie
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
                $option['samesite']
            );
        }
    }

    /**
     * Note: 保存Cookie
     * Date: 2022-10-08
     * Time: 18:31
     * @param string $name cookie名称
     * @param string $value cookie值
     * @param int $expire cookie过期时间
     * @param string $path cookie路径
     * @param string $domain 有效域名
     * @param bool $secure 是否仅仅通过HTTPS
     * @param bool $httponly 仅通过httponly访问
     * @param string $samesite 防止CSRF攻击和用户追踪
     * @return void
     */
    protected function saveCookie(string $name, string $value, int $expire, string $path, string $domain, bool $secure, bool $httponly, string $samesite)
    {
        if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
            setcookie($name, $value, [
                'expires' => $expire,
                'path' => $path,
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => $httponly,
                'samesite' => $samesite,
            ]);
        } else {
            setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        }
    }
}