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
     * URL变量
     * @var array
     */
    protected $vars = [];

    /**
     * URL根地址
     * @var string
     */
    protected $root = '';

    /**
     * HTTPS
     * @var bool
     */
    protected $https;

    /**
     * URL后缀
     * @var bool|string
     */
    protected $suffix = true;

    /**
     * URL域名
     * @var bool|string
     */
    protected $domain = true;

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
        $url = $this->url;
        $suffix = $this->suffix;
        $domain = $this->domain;
        $request = $this->app->request;
        $vars = $this->vars;

        //获取使用的命名标识
        if (strpos($url, '[') === 0 && $pos = strpos($url, ']')) {
            $name = substr($url, 1, $pos - 1);
        }

        //解析url
        if (strpos($url, '://') === false && strpos($url, '/') !== 0) {
            $info = parse_url($url);
            $url = !empty($info) ? $info['path'] : '';

            if (isset($info['fragment'])) {
                $anchor = $info['fragment'];

                if (strpos($anchor, '?') !== false) {
                    [$anchor, $info['query']] = explode('?', $anchor, 2);
                }

                if (strpos($anchor, '@') !== false) {
                    [$anchor, $domain] = explode('@', $anchor, 2);
                }
            } elseif (strpos($url, '@') !== false && strpos($url, '\\') === false) {
                [$url, $domain] = explode('@', $url, 2);
            }
        }

        //获取路由规则和路由变量
        if ($url) {
            $checkName = isset($name) ? $name : $url . (isset($info['query']) ? '?' . $info['query'] : '');
            $checkDomain = $domain && is_string($domain) ? $domain : null;

            $rule = $this->route->getName($checkName, $checkDomain);
            if (empty($rule) && isset($info['query'])) {
                $rule = $this->route->getName($url, $checkDomain);
                parse_str($info['query'], $params);
                $vars = array_merge($params, $vars);
                unset($info['query']);
            }
        }

        if (!empty($url) && $match = $this->getRuleUrl($rule, $vars, $domain)) {
            $url = $match[0];
            if ($domain && !empty($match[1])) {
                $domain = $match[1];
            }
            if (!is_null($match[2])) {
                $suffix = $match[2];
            }
        } elseif (!empty($url) && isset($name)) {
            throw new \InvalidArgumentException('route name not exists:' . $name);
        } else {
            $binds = $this->route->getBind();
            foreach ($binds as $key => $val) {
                if (is_string($val) && strpos($url, $val) === 0 && substr_count($val, '/') > 1) {
                    $url = substr($url, strlen($val) + 1);
                    $domain = $key;
                    break;
                }
            }

            $url = $this->parseUrl($url, $domain);

            if (isset($info['query'])) {
                parse_str($info['query'], $params);
                $vars = array_merge($params, $vars);
            }
        }

        //组装URL
        $depr = $this->route->config('pathinfo_depr');
        $url = str_replace('/', $depr, $url);

        $file = $request->baseFile();
        $url = rtrim($file, '/') . '/' . $url;

        if (substr($url, -1) == '/' || $url == '') {
            $suffix = '';
        } else {
            $suffix = $this->parseSuffix($suffix);
        }

        $anchor = !empty($anchor) ? '#' . $anchor : '';

        if (!empty($vars)) {
            if ($this->route->config('url_common_param')) {
                $vars = http_build_query($vars);
                $url .= $suffix . ($vars ? '?' . $vars : '') . $anchor;
            } else {
                foreach ($vars as $var => $val) {
                    $val = (string)$val;
                    if ($val !== '') {
                        $url .= $depr . $var . $depr . urlencode($val);
                    }
                }

                $url .= $suffix . $anchor;
            }
        } else {
            $url .= $suffix . $anchor;
        }

        $domain = $this->parseDomain($url, $domain);

        return $domain . rtrim($this->root, '/') . '/' . ltrim($url, '/');
    }

    /**
     * Note: 获取匹配的路由信息
     * Date: 2023-08-04
     * Time: 16:13
     * @param array $rule 路由信息
     * @param array $vars 路由变量
     * @param mixed $allowDomain 域名
     * @return array
     */
    protected function getRuleUrl(array $rule, array &$vars = [], $allowDomain = '')
    {
        $request = $this->app->request;
        if (is_string($allowDomain) && strpos($allowDomain, '.') === false) {
            $allowDomain .= '.' . $request->rootDomain();
        }

        $port = $request->port();

        foreach ($rule as $item) {
            $url = $item['rule'];
            $pattern = $this->parseVar($url);
            $domain = $item['domain'];
            $suffix = $item['suffix'];

            if ($domain == '-') {
                $domain = is_string($allowDomain) ? $allowDomain : $request->host(true);
            }
            if (is_string($allowDomain) && $domain != $allowDomain) {
                continue;
            }

            if ($port && !in_array($port, [80, 443])) {
                $domain .= ':' . $port;
            }

            if (empty($pattern)) {
                return [rtrim($url, '?/-'), $domain, $suffix];
            }

            $type = $this->route->config('url_common_param');
            $keys = [];
            foreach ($pattern as $key => $val) {
                if (isset($vars[$key])) {
                    $url = str_replace(['[:' . $key . ']', '<' . $key . '?>', '[' . $key . ']', '<' . $key . '>'], $type ? (string)$vars[$key] : urlencode((string)$vars[$key]), $url);
                    $keys[] = $key;
                    $result = [rtrim($url, '?/-'), $domain, $suffix];
                } elseif ($val == 2) {
                    $url = str_replace(['[:' . $key . ']', '<' . $key . '?>'], '', $url);
                    $result = [rtrim($url, '?/-'), $domain, $suffix];
                } else {
                    $keys[] = [];
                    $result = null;
                    break;
                }
            }

            $vars = array_diff_key($vars, array_flip($keys));

            if (isset($result)) {
                return $result;
            }
        }

        return [];
    }

    /**
     * Note: 分析路由规则中变量
     * Date: 2023-08-04
     * Time: 16:18
     * @param string $rule 路由规则
     * @return array
     */
    protected function parseVar(string $rule)
    {
        $var = [];
        if (preg_match_all('/<\w+\??>/', $rule, $matches)) {
            foreach ($matches[0] as $name) {
                $optional = false;

                if (strpos($name, '?')) {
                    $name = substr($name, 1, -2);
                    $optional = true;
                } else {
                    $name = substr($name, 1, -1);
                }

                $var[$name] = $optional ? 2 : 1;
            }
        }

        return $var;
    }

    /**
     * Note: 解析URL地址
     * Date: 2023-08-04
     * Time: 17:09
     * @param string $url URL地址
     * @param string|bool $domain 域名
     * @return string
     */
    protected function parseUrl(string $url, &$domain)
    {
        $request = $this->app->request;

        if (strpos($url, '/') === 0) {
            $url = substr($url, 1);
        } elseif (strpos($url, '\\') !== false) {
            $url = ltrim(str_replace('\\', '/', $url), '/');
        } elseif (strpos($url, '@') === 0) {
            $url = substr($url, 1);
        } elseif ($url === '') {
            $url = $request->controller() . '/' . $request->action();
        } else {
            $controller = $request->controller();
            $path = explode('/', $url);
            $action = array_pop($path);
            $controller = empty($path) ? $controller : array_pop($path);

            $url = $controller . '/' . $action;
        }

        return $url;
    }

    /**
     * Note: 解析URL后缀
     * Date: 2023-08-04
     * Time: 18:09
     * @param string|bool $suffix 后缀
     * @return string
     */
    protected function parseSuffix($suffix)
    {
        if ($suffix) {
            $suffix = $suffix === true ? $this->route->config('url_html_suffix') : $suffix;

            if (is_string($suffix) && $pos = strpos($suffix, '|')) {
                $suffix = substr($suffix, 0, $pos);
            }
        }

        return (empty($suffix) || strpos($suffix, '.') === 0) ? (string)$suffix : '.' . $suffix;
    }

    /**
     * Note: 检查域名
     * Date: 2023-08-04
     * Time: 18:19
     * @param string $url URL
     * @param string|bool $domain 域名
     * @return string
     */
    protected function parseDomain(string &$url, $domain)
    {
        if (!$domain) {
            return '';
        }

        $request = $this->app->request;
        $rootDomain = $request->rootDomain();

        if ($domain === true) {
            $domain = $request->host();
            $domains = $this->route->getDomains();

            if (!empty($domains)) {
                $routeDomain = array_keys($domains);
                foreach ($routeDomain as $domainPrefix) {
                    if (0 === strpos($domainPrefix, '*.') && strpos($domain, ltrim($domainPrefix, '*.')) !== false) {
                        foreach ($domains as $key => $rule) {
                            $rule = is_array($rule) ? $rule[0] : $rule;
                            if (is_string($rule) && false === strpos($key, '*') && 0 === strpos($url, $rule)) {
                                $url = ltrim($url, $rule);
                                $domain = $key;

                                // 生成对应子域名
                                if (!empty($rootDomain)) {
                                    $domain .= $rootDomain;
                                }
                                break;
                            } elseif (false !== strpos($key, '*')) {
                                if (!empty($rootDomain)) {
                                    $domain .= $rootDomain;
                                }

                                break;
                            }
                        }
                    }
                }
            }
        } elseif (strpos($domain, '.') === false && strpos($domain, $rootDomain) !== 0) {
            $domain .= '.' . $rootDomain;
        }

        if (strpos($domain, '://') !== false) {
            $scheme = '';
        } else {
            $scheme = $this->https || $request->isSsl() ? 'https://' : 'http://';
        }

        return $scheme . $domain;
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