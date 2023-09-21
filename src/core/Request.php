<?php
declare(strict_types=1);

namespace Enna\Framework;

use ArrayAccess;
use Enna\Framework\Route\Rule;
use Enna\Framework\File\UploadFile;

/**
 * 请求管理类
 * Class Request
 * @package Enna\Framework
 */
class Request implements ArrayAccess
{
    /**
     * 兼容PATH_INFO获取
     * @var array
     */
    protected $pathinfoFetch = [];

    /**
     * PATHINFO变量名,用于兼容模式
     * @var string
     */
    protected $varPathinfo = 's';

    /**
     * 请求类型伪装变量
     * @var string
     */
    protected $varMethod = '_method';

    /**
     * 表单ajax伪装变脸
     * @var string
     */
    protected $varAjax = '_ajax';

    /**
     * 表单pjax伪装变量
     * @var string
     */
    protected $varPjax = '_pjax';

    /**
     * HTTPS代理标识
     * @var string
     */
    protected $httpsAgentName = '';

    /**
     * 前端代理服务器IP
     * @var array
     */
    protected $proxyServerIp = [];

    /**
     * 前端代理服务器真实IP头
     * @var array
     */
    protected $proxyServerIpHeader = ['HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_X_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP'];

    /**
     * 是否合并参数
     * @var bool
     */
    protected $mergeParam = false;

    /**
     * 请求参数
     * @var array
     */
    protected $param = [];

    /**
     * GET参数
     * @var array
     */
    protected $get = [];

    /**
     * POST参数
     * @var array
     */
    protected $post = [];

    /**
     * PUT参数
     * @var array
     */
    protected $put;

    /**
     * REQUEST参数
     * @var array
     */
    protected $request = [];

    /**
     * 路由规则对象
     * @var Rule
     */
    protected $rule;

    /**
     * 路由参数
     * @var array
     */
    protected $route = [];

    /**
     * 中间件传递参数
     * @var array
     */
    protected $middleware = [];

    /**
     * FILE参数
     * @var array
     */
    protected $file = [];

    /**
     * 请求方法
     * @var string
     */
    protected $method;

    /**
     * SESSION对象
     * @var Session
     */
    protected $session;

    /**
     * COOKIE数据
     * @var array
     */
    protected $cookie = [];

    /**
     * 访问ROOT地址
     * @var string
     */
    protected $root;

    /**
     * pathinfo
     * @var string
     */
    protected $pathinfo;

    /**
     * pathinfo(不含后缀)
     * @var string
     */
    protected $path;

    /**
     * 当前请求的IP地址
     * @var string
     */
    protected $realIP;

    /**
     * 当前host(含端口)
     * @var string
     */
    protected $host;

    /**
     * 域名(含协议及端口)
     * @var string
     */
    protected $domain;

    /**
     * 根域名
     * @var string
     */
    protected $rootDomain = '';

    /**
     * 子域名
     * @var string
     */
    protected $subDomain;

    /**
     * 泛域名
     * @var string
     */
    protected $panDomain;

    /**
     * 当前URL地址
     * @var string
     */
    protected $url;

    /**
     * 基础URL
     * @var string
     */
    protected $baseUrl;

    /**
     * 当前执行的文件
     * @var string
     */
    protected $baseFile;

    /**
     * server信息
     * @var array
     */
    protected $server = [];

    /**
     * header头
     * @var array
     */
    protected $header = [];

    /**
     * 资源类型定义
     * @var array
     */
    protected $mimeType = [
        'xml' => 'application/xml,text/xml',
        'json' => 'application/json,text/json',
        'html' => 'text/html,*/*',
        'js' => 'text/javascript,application/javascript',
        'css' => 'text/css',
        'yaml' => 'text/yaml',
        'text' => 'text/plain',
        'image' => 'image/png,image/jpg,image/jpeg,image/gif,image/webp,image/*',
        'csv' => 'text/csv'
    ];

    /**
     * 当前请求内容
     * @var string
     */
    protected $content;

    /**
     * Env对象
     * @var Env
     */
    protected $env;

    /**
     * 全局过滤规则
     * @var array
     */
    protected $filter;

    /**
     * php://input内容
     * @var string
     */
    protected $input;

    /**
     * 当前控制器
     * @var string
     */
    protected $controller;

    /**
     * 当前操作
     * @var string
     */
    protected $action;

    /**
     * 请求安全Key
     * @var bool
     */
    protected $secureKey;

    public function __construct()
    {
        $this->input = file_get_contents('php://input');
    }

    public static function __make(App $app)
    {
        $request = new static();

        if (function_exists('apache_request_headers') && $result = apache_request_headers()) {
            $header = $result;
        } else {
            $header = [];
            $server = $_SERVER;
            foreach ($server as $key => $val) {
                if (strpos($key, 'HTTP_') === 0) {
                    $key = str_replace('_', '-', strtolower(substr($key, 5)));
                    $header[$key] = $val;
                }
            }
            if ($server['CONTENT_TYPE']) {
                $header['content-type'] = $server['CONTENT_TYPE'];
            }
            if ($server['CONTENT_LENGTH']) {
                $header['content-length'] = $server['CONTENT_LENGTH'];
            }
        }

        $request->header = array_change_key_case($header);
        $request->server = $_SERVER;
        $request->env = $app->env;

        $inputData = $request->getInputData($request->input);

        $request->get = $_GET;
        $request->post = $_POST ?? $inputData;
        $request->put = $inputData;
        $request->request = $_REQUEST;
        $request->cookie = $_COOKIE;
        $request->file = $_FILES;

        return $request;
    }

    /**
     * Note: 获取input数据
     * Date: 2022-09-29
     * Time: 14:00
     * @param mixed $content 内容
     * @return array
     */
    protected function getInputData($content)
    {
        $contentType = $this->contentType();

        if ($contentType == 'application/x-www-form-urlencoded') {
            parse_str($content, $data);
            return $data;
        } elseif (strpos($contentType, 'json') !== false) {
            $data = (array)json_decode($content, true);
            return $data;
        }
        return [];
    }

    /**
     * Note: 获取当前请求参数
     * Date: 2022-09-30
     * Time: 15:59
     * @param string|array $name 变量名
     * @param null $default 默认值
     * @param string $filter 过滤方法
     * @return mixed
     */
    public function param($name = '', $default = null, $filter = '')
    {
        if (empty($this->mergeParam)) {
            $method = $this->method(true);

            // 自动获取请求变量
            switch ($method) {
                case 'POST':
                    $vars = $this->post(false);
                    break;
                case 'PUT':
                case 'DELETE':
                case 'PATCH':
                    $vars = $this->put(false);
                    break;
                default:
                    $vars = [];
            }

            $this->param = array_merge($this->param, $this->get(false), $vars, $this->route(false));

            $this->mergeParam = true;
        }

        if (is_array($name)) {
            return $this->only($name, $this->param, $filter);
        }

        return $this->input($this->param, $name, $default, $filter);
    }

    /**
     * Note: 获取包含文件在内的请求参数
     * Date: 2023-08-11
     * Time: 16:42
     * @param string|array $name 变量名
     * @param string|array $filter 过滤方法
     * @return mixed
     */
    public function all($name = '', $filter = '')
    {
        $data = array_merge($this->param, $this->file ?: []);

        if (is_array($name)) {
            $data = $this->only($name, $data, $filter);
        } else {
            $data = $data[$name] ?? null;
        }

        return $data;
    }

    /**
     * Note: 获取POST请求参数
     * Date: 2022-09-30
     * Time: 16:13
     * @param string|array $name 变量名
     * @param mixed $default 默认值
     * @param string|array $filter 过滤方法
     * @return mixed
     */
    public function post($name = '', $default = null, $filter = '')
    {
        if (is_array($name)) {
            return $this->only($name, $this->post, $filter);
        }

        return $this->input($this->post, $name, $default, $filter);
    }

    /**
     * Note: 获取GET请求参数
     * Date: 2022-09-30
     * Time: 17:51
     * @param string|array $name 变量名
     * @param mixed $default 默认值
     * @param string|array $filter 过滤方法
     * @return
     */
    public function get($name = '', $default = null, $filter = '')
    {
        if (is_array($name)) {
            return $this->only($name, $this->get, $filter);
        }

        return $this->input($this->get, $name, $default, $filter);
    }

    /**
     * Note: 获取中间件传递的参数
     * Date: 2023-07-07
     * Time: 14:23
     * @param mixed $name 参数名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function middleware($name, $default = null)
    {
        return $this->middleware[$name] ?? $default;
    }

    /**
     * Note: 获取PUT参数
     * Date: 2022-11-16
     * Time: 14:43
     * @param string|array $name 变量名
     * @param mixed $default 默认值
     * @param string|array $filter 过滤方法
     * @return mixed
     */
    public function put($name = '', $default = null, $filter = '')
    {
        if (is_array($name)) {
            return $this->only($name, $this->put, $filter);
        }

        return $this->input($this->put, $name, $default, $filter);
    }

    /**
     * Note: 设置获取delete参数
     * Date: 2023-08-11
     * Time: 16:50
     * @param string|array $name 变量名
     * @param mixed $default 默认值
     * @param string|array $filter 过滤方法
     * @return mixed
     */
    public function delete($name = '', $default = null, $filter = '')
    {
        return $this->put($name, $default, $filter);
    }

    /**
     * Note: 设置获取patch参数
     * Date: 2023-08-11
     * Time: 16:51
     * @param string|array $name 变量名
     * @param mixed $default 默认值
     * @param string|array $filter 过滤方法
     * @return mixed
     */
    public function patch($name = '', $default = null, $filter = '')
    {
        return $this->put($name, $default, $filter);
    }

    /**
     * Note: 获取request变量
     * Date: 2023-08-11
     * Time: 16:54
     * @param string|array $name 变量名
     * @param mixed $default 默认值
     * @param string|array $filter 过滤方法
     * @return mixed
     */
    public function request($name = '', $default = null, $filter = '')
    {
        if (is_array($name)) {
            return $this->only($name, $this->request, $filter);
        }

        return $this->input($this->request, $name, $default, $filter);
    }

    /**
     * Note: 获取当前header信息
     * Date: 2022-09-29
     * Time: 14:12
     * @param string $name header名称
     * @param string $default 默认值
     * @return string|array
     */
    public function header(string $name = '', string $default = '')
    {
        if ($name == '') {
            return $this->header;
        }

        $name = str_replace('_', '-', strtolower($name));

        return $this->header[$name] ?? $default;
    }

    /**
     * Note: 获取变量,支持默认值和过滤
     * Date: 2022-09-30
     * Time: 16:15
     * @param array $data 数据
     * @param string $name 字段名
     * @param mixed $default 默认值
     * @param string|array $filter 过滤函数
     * @return mixed
     */
    public function input(array $data = [], $name = '', $default = null, $filter = '')
    {
        if (empty($name)) {
            return $data;
        }

        if ($name) {
            if (strpos($name, '/')) {
                [$name, $type] = explode('/', $name);
            }

            $data = $this->getData($data, $name);

            if (is_null($data)) {
                return $default;
            }
            if (is_object($data)) {
                return $data;
            }
        }

        $data = $this->filterData($data, $filter, $name, $default);

        if (isset($type) && $data !== $default) {
            $this->typeCast($data, $type);
        }

        return $data;
    }

    /**
     * Note: 获取指定的参数
     * Date: 2023-05-05
     * Time: 18:07
     * @param array $name 变量名
     * @param string $data 数据
     * @param string $filter 过滤方法
     * @return array
     */
    public function only(array $name, $data = 'param', $filter = '')
    {
        $data = is_array($data) ? $data : $this->$data();

        $item = [];
        foreach ($name as $key => $val) {
            if (is_int($key)) {
                $default = null;
                $key = $val;
                if (!isset($data[$key])) {
                    continue;
                }
            } else {
                $default = $val;
            }

            $item[$key] = $this->filterData($data[$key] ?? $default, $filter, $key, $default);
        }

        return $item;
    }

    /**
     * Note: 排除指定参数获取
     * Date: 2023-08-12
     * Time: 16:22
     * @param array $name 变量名
     * @param string $type 变量类型
     * @return mixed
     */
    public function except(array $name, string $type = 'param')
    {
        $param = $this->$type();

        foreach ($name as $key) {
            if (isset($param[$key])) {
                unset($param[$key]);
            }
        }

        return $param;
    }

    /**
     * Note: 获取环境变量
     * Date: 2023-08-11
     * Time: 17:00
     * @param string $name 数据名称
     * @param string $default 默认值
     * @return mixed
     */
    public function env(string $name = '', string $default = null)
    {
        if (empty($name)) {
            return $this->env->get();
        } else {
            $name = strtolower($name);
        }

        return $this->env->get($name, $default);
    }

    /**
     * Note: 获取session数据
     * Date: 2023-08-11
     * Time: 17:02
     * @param string $name 数据名称
     * @param string $default 默认值
     * @return mixed
     */
    public function session(string $name = '', $default = null)
    {
        if ($name === '') {
            return $this->session->all();
        }

        return $this->session->get($name, $default);
    }

    /**
     * Note: 获取Cookie数据
     * Date: 2023-02-28
     * Time: 14:04
     * @param string $name cookie名称
     * @param mixed $default cookie默认值
     * @param string|array $fiter 过滤方法
     * @return mixed
     */
    public function cookie(string $name = '', $default = null, $filter = '')
    {
        if (!empty($name)) {
            $data = $this->getData($this->cookie, $name, $default);
        } else {
            $data = $this->cookie;
        }

        $filter = $this->getFilter($filter, $default);

        if (is_array($data)) {
            array_walk_recursive($data, [$this, 'filterValue'], $filter);
        } else {
            $this->filterValue($data, $name, $filter);
        }

        return $data;
    }

    /**
     * Note: 获取SERVER参数
     * Date: 2022-09-28
     * Time: 19:00
     * @param string $name 数据名称
     * @param string $default 默认值
     * @return mixed
     */
    public function server(string $name = '', string $default = '')
    {
        if (empty($name)) {
            return $this->server;
        } else {
            $name = strtoupper($name);
        }

        return $this->server[$name] ?? $default;
    }

    /**
     * Note: 获取上传的文件信息
     * Date: 2023-01-06
     * Time: 10:30
     * @param string $name 名称
     * @return null|array|UploadFile
     */
    public function file(string $name = '')
    {
        $files = $this->file;
        if (!empty($files)) {
            if (strpos($name, ',')) {
                [$name, $sub] = explode(',', $name);
            }

            $array = $this->dealUploadFile($files, $name);

            if ($name == '') {
                return $array;
            } elseif (isset($sub) && isset($array[$name][$sub])) {
                return $array[$name][$sub];
            } elseif (isset($array[$name])) {
                return $array[$name];
            }
        }
    }

    /**
     * Note: 处理上传的文件
     * Date: 2023-01-06
     * Time: 11:40
     * @param array $files 文件信息
     * @param string $name 文件名
     * @return array
     * @throws Exception
     */
    protected function dealUploadFile(array $files, string $name)
    {
        $array = [];
        foreach ($files as $key => $file) {
            if (is_array($file['name'])) {
                $item = [];

                $keys  = array_keys($file);
                $count = count($file['name']);
                for ($i = 0; $i < $count; $i++) {
                    if ($file['error'][$i] > 0) {
                        if ($name == $key) {
                            $this->throwUploadError($file['error'][$i]);
                        } else {
                            continue;
                        }
                    }

                    $temp['key'] = $key;

                    foreach ($keys as $temp_key) {
                        $temp[$temp_key] = $file[$temp_key][$i];
                    }

                    $item[] = new UploadFile($temp['tmp_name'], $temp['name'], $temp['type'], $temp['error']);
                }

                $array[$key] = $item;
            } else {
                if ($file instanceof File) {
                    $array[$key] = $file;
                } else {
                    if ($file['error'] > 0) {
                        if ($name == $key) {
                            $this->throwUploadError($file['error']);
                        } else {
                            continue;
                        }
                    }
                    
                    $array[$key] = new UploadFile($file['tmp_name'], $file['name'], $file['type'], $file['error']);
                }
            }
        }

        return $array;
    }

    /**
     * Note: 抛出上传错误
     * User: enna
     * Date: 2023-01-06
     * Time: 12:00
     * @param int $error
     * @throws Exception
     */
    protected function throwUploadError(int $error)
    {
        $fileUploadErrors = [
            1 => '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值',
            2 => '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值',
            3 => '文件只有部分被上传',
            4 => '没有文件被上传',
            6 => '找不到临时文件夹',
            7 => '文件写入失败',
        ];

        $msg = $fileUploadErrors[$error];

        throw new Exception($msg, $error);
    }

    /**
     * Note: 设置全局过滤规则
     * Date: 2023-02-28
     * Time: 14:27
     * @param mixed $filter 过滤规则
     * @return $this|mixed
     */
    public function filter($filter = null)
    {
        if (is_null($filter)) {
            return $this->filter;
        }

        $this->filter = $filter;

        return $this;
    }

    /**
     * Note: 解析过滤器
     * Date: 2023-02-28
     * Time: 17:53
     * @param mixed $filter 过滤器:函数,正则,filter_id
     * @param mixed $default 默认值
     * @return array
     */
    protected function getFilter(string $filter, $default)
    {
        if (is_null($filter)) {
            $filter = [];
        } else {
            $filter = $filter ?: $this->filter;
            if (is_string($filter) && strpos($filter, '/') !== false) {
                $filter = explode(',', $filter);
            } else {
                $filter = (array)$filter;
            }
        }

        $filter[] = $default;

        return $filter;
    }

    /**
     * Note: 设置路由规则对象
     * Date: 2022-10-26
     * Time: 14:10
     * @param Rule $rule 路由规则对象
     * @return $this
     */
    public function setRule(Rule $rule)
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * Note: 获取路由规则对象
     * Date: 2022-10-26
     * Time: 14:13
     * @return Rule|null
     */
    public function rule()
    {
        return $this->rule;
    }

    /**
     * Note: 设置路由变量
     * Date: 2022-11-10
     * Time: 17:16
     * @param array $route 路由变量
     * @return $this
     */
    public function setRoute(array $route)
    {
        $this->route = array_merge($this->route, $route);
        $this->mergeParam = false;

        return $this;
    }

    /**
     * Note: 获取路由参数
     * Date: 2022-09-30
     * Time: 17:51
     * @param string|array $name 变量名
     * @param mixed $default 默认值
     * @param string|array $filter 过滤方法
     * @return mixed
     */
    public function route($name = '', $default = null, $filter = '')
    {
        if (is_array($name)) {
            $this->only($name, $this->route, $filter);
        }
        return $this->input($this->route, $name, $default, $filter);
    }

    /**
     * Note: 设置当前控制器
     * Date: 2022-10-09
     * Time: 18:14
     * @param string $controller
     * @return $this
     */
    public function setController(string $controller)
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * Note: 获取当前的控制器名
     * Date: 2023-08-08
     * Time: 10:38
     * @param bool $convert 转换为小写
     * @return string
     */
    public function controller(bool $convert = false)
    {
        $name = $this->controller ?: '';

        return $convert ? strtolower($name) : $name;
    }

    /**
     * Note: 设置当前操作
     * Date: 2022-10-09
     * Time: 18:15
     * @param string $action
     * @return $this
     */
    public function setAction(string $action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Note: 获取当前操作名
     * Date: 2022-10-10
     * Time: 17:50
     * @paras bool $convert 转换为小写
     * @return string
     */
    public function action(bool $convert = false)
    {
        $name = $this->action ?: '';

        return $convert ? strtolower($name) : $name;
    }

    /**
     * Note: 设置或者获取当前请求的content
     * Date: 2023-08-11
     * Time: 17:29
     * @return string
     */
    public function getContent()
    {
        if (is_null($this->content)) {
            $this->content = $this->input;
        }

        return $this->content;
    }

    /**
     * Note: 获取当前请求的php://input
     * Date: 2023-08-11
     * Time: 17:30
     * @return string
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Note: 检查是否存在某个请求参数
     * Date: 2023-02-28
     * Time: 11:37
     * @param string $name 变量名
     * @param string $type 变量类型
     * @param bool $checkEmpty 是否检查空值
     * @return bool
     */
    public function has(string $name, string $type = 'param', bool $checkEmpty = false)
    {
        if (!in_array($type, ['param', 'get', 'post', 'put', 'delete', 'header', 'server', 'request', 'file', 'cookie', 'session', 'env', 'route'])) {
            return false;
        }

        $param = empty($this->$type) ? $this->$type() : $this->$type;

        if (is_object($param)) {
            return $param->has($name);
        }

        foreach (explode('.', $name) as $val) {
            if (isset($param[$val])) {
                $param = $param[$val];
            } else {
                return false;
            }
        }

        if ($param === '' && $checkEmpty) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Note: 获取过滤后的值
     * Date: 2023-02-28
     * Time: 17:58
     * @param mixed $value 过滤前的值
     * @param mixed $name 过滤的值的名称
     * @param array $filters 过滤方法+默认值
     * @return mixed
     */
    public function filterValue(&$value, $name, $filters)
    {
        $default = array_pop($filters);

        foreach ($filters as $filter) {
            if (is_callable($filter)) {
                $value = call_user_func($filter, $value);
            } else {
                if (is_string($filter) && strpos($filter, '/') !== false) {
                    if (!preg_match($filter, $value)) {
                        $value = $default;
                        break;
                    }
                } elseif (!empty($filter)) {
                    $value = filter_var($value, is_int($filter) ? $filter : filter_id($filter));
                    if ($value === false) {
                        $value = $default;
                        break;
                    }
                }
            }
        }

        return $value;
    }

    /**
     * Note: 过滤数据
     * Date: 2022-09-30
     * Time: 17:20
     * @param string|array $data 数据
     * @param string|array $filter 过滤器
     * @param string $name 字段名
     * @param mixed $default 默认值
     * @return mixed
     */
    protected function filterData($data, $filter = '', $name = '', $default = null)
    {
        //解析过滤器
        $filter = $this->getFilter($filter, $default);

        if (is_array($data)) {
            array_walk_recursive($data, [$this, 'filterValue'], $filter);
        } else {
            $this->filterValue($data, $name, $filter);
        }

        return $data;
    }


    /**
     * Note: 强制类型转换
     * Date: 2022-09-30
     * Time: 17:44
     * @param mixed $data 数据
     * @param string $type 类型
     * @return void
     */
    protected function typeCast(&$data, string $type)
    {
        switch (strtolower($type)) {
            case 'a':
                $data = (array)$data;
                break;
            case 's':
                $data = (string)$data;
                break;
            case 'd':
                $data = (int)$data;
                break;
            case 'f':
                $data = (float)$data;
                break;
            case 'b':
                $data = (boolean)$data;
                break;
        }
    }

    /**
     * Note: 获取数据
     * Date: 2023-02-28
     * Time: 14:09
     * @param array $data 数据源
     * @param string $name 名称
     * @param mixed $default 默认值
     * @return mixed
     */
    protected function getData(array $data, string $name, $default = null)
    {
        foreach (explode('.', $name) as $val) {
            if (isset($data[$val])) {
                $data = $data[$val];
            } else {
                $data = $default;
            }
        }

        return $data;
    }

    /**
     * Note: 获取根域名
     * Date: 2022-10-28
     * Time: 17:15
     * @return string
     */
    public function rootDomain()
    {
        $root = $this->rootDomain;

        if (is_null($root)) {
            $item = explode(',', $this->host());
            $count = count($item);
            $root = $count > 1 ? $item[$count - 2] . '.' . $item[$count - 1] : $item[0];
        }

        return $root;
    }

    /**
     * Note: 设置当前包含协议的域名
     * Date: 2023-08-11
     * Time: 14:08
     * @param string $domain
     * @return $this
     */
    public function setDomain(string $domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Note: 获取当前包含协议的域名
     * Date: 2023-08-11
     * Time: 14:09
     * @param bool $port
     * @return string
     */
    public function domain(bool $port = false)
    {
        return $this->scheme() . '://' . $this->host($port);
    }

    /**
     * Note: 设置当前子域名
     * Date: 2023-08-11
     * Time: 14:25
     * @param string $domain 域名
     * @return $this
     */
    public function setSubDomain(string $domain)
    {
        $this->subDomain = $domain;

        return $this;
    }

    /**
     * Note: 获取当前子域名
     * Date: 2022-10-28
     * Time: 17:12
     * @return string
     */
    public function subDomain()
    {
        if (is_null($this->subDomain)) {
            $rootDomain = $this->rootDomain();

            if ($rootDomain) {
                $sub = stristr($this->host(), $rootDomain, true);
                $this->subDomain = $sub ? rtrim($sub, '.') : '';
            } else {
                $this->subDomain = '';
            }
        }

        return $this->subDomain;
    }

    /**
     * Note: 设置当前泛域名的值
     * Date: 2023-07-17
     * Time: 17:51
     * @param string $domain 域名
     * @return $this
     */
    public function setPanDomain(string $domain)
    {
        $this->panDomain = $domain;

        return $this;
    }

    /**
     * Note: 获取当前泛域名值
     * Date: 2023-07-17
     * Time: 17:47
     * @return string
     */
    public function panDomain()
    {
        return $this->panDomain ?: '';
    }

    /**
     * Note: 设置当前请求的host(包含端口)
     * Date: 2023-08-11
     * Time: 18:36
     * @param string $host 主机名(含端口)
     * @return $this
     */
    public function setHost(string $host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Note: 当前请求的host
     * Date: 2022-09-28
     * Time: 18:29
     * @param bool $strict 只获取host
     * @return string
     */
    public function host(bool $strict = false): string
    {
        if ($this->host) {
            return $this->host;
        } else {
            $host = strval($this->server('HTTP_X_FORWARDED_HOST') ?: $this->server('HTTP_HOST'));
        }

        return $strict === true && strpos($host, ':') ? strstr($host, ':', true) : $host;
    }

    /**
     * Note: 设置当前完整URL
     * Date: 2023-07-12
     * Time: 11:28
     * @param string $url URL地址
     * @return $this
     */
    public function setUrl(string $url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Note: 获取当前完整URL
     * Date: 2023-07-12
     * Time: 9:43
     * @param bool $complete 是否包含完整域名
     * @return string
     */
    public function url(bool $complete = false)
    {
        if ($this->url) {
            $url = $this->url;
        } elseif ($this->server('HTTP_X_REWRITE_URL')) {
            $url = $this->server('HTTP_X_REWRITE_URL');
        } elseif ($this->server('REQUEST_URI')) {
            $url = $this->server('REQUEST_URI');
        } elseif ($this->server('ORIG_PATH_INFO')) {
            $url = $this->server('ORIG_PATH_INFO') . (!empty($this->server('QUERY_STRING')) ? '?' . $this->server('QUERY_STRING') : '');
        } elseif (isset($_SERVER['argv'][1])) {
            $url = $_SERVER['argv'][1];
        } else {
            $url = '';
        }

        return $complete ? $this->domain() . $url : $url;
    }

    /**
     * Note: 设置当前URL  不含QUERY_STRING
     * Date: 2023-04-18
     * Time: 15:35
     * @param string $url URL地址
     * @return $this
     */
    public function setBaseUrl(string $url)
    {
        $this->baseUrl = $url;

        return $this;
    }

    /**
     * Note: 获取当前URL 不含QUERY_STRING
     * Date: 2023-04-18
     * Time: 15:33
     * @param bool $complete 是否包含完整域名
     * @return string
     */
    public function baseUrl(bool $complete = false)
    {
        if (!$this->baseUrl) {
            $str = $this->url();
            $this->baseUrl = strpos($str, '?') ? strstr($str, '?', true) : $str;
        }

        return $complete ? $this->domain() . $this->baseUrl : $this->baseUrl;
    }

    /**
     * Note: 获取当前执行的文件 SCRIPT_FILE
     * Date: 2023-08-04
     * Time: 17:55
     * @param bool $complete 是否包含完整域名
     * @return string
     */
    public function baseFile(bool $complete = true)
    {
        if (!$this->baseFile) {
            $url = '';
            if (!$this->isCli()) {
                $script_name = basename($this->server('SCRIPT_FILENAME'));
                if (basename($this->server('SCRIPT_NAME')) === $script_name) {
                    $url = $this->server('SCRIPT_NAME');
                } elseif (basename($this->server('PHP_SELF')) === $script_name) {
                    $url = $this->server('PHP_SELF');
                } elseif (basename($this->server('ORIG_SCRIPT_NAME')) === $script_name) {
                    $url = $this->server('ORIG_SCRIPT_NAME');
                } elseif (($pos = strpos($this->server('PHP_SELF'), '/' . $script_name)) !== false) {
                    $url = substr($this->server('SCRIPT_NAME'), 0, $pos) . '/' . $script_name;
                } elseif ($this->server('DOCUMENT_ROOT') && strpos($this->server('SCRIPT_FILENAME'), $this->server('DOCUMENT_ROOT')) === 0) {
                    $url = str_replace('\\', '/', str_replace($this->server('DOCUMENT_ROOT'), '', $this->server('SCRIPT_FILENAME')));
                }
            }

            $this->baseFile = $url;
        }

        return $complete ? $this->domain() . $this->baseFile : $this->baseFile;
    }

    /**
     * Note: 设置URL访问根地址
     * Date: 2023-08-11
     * Time: 14:27
     * @param string $url URL根地址
     * @return $this
     */
    public function setRoot(string $url)
    {
        $this->root = $url;

        return $this;
    }

    /**
     * Note: 获取URL
     * Date: 2023-08-11
     * Time: 14:34
     * @param bool $complete
     * @return string
     */
    public function root(bool $complete = false)
    {
        if (!$this->root) {
            $file = $this->baseFile();
            if ($file && strpos($this->url(), $file) !== 0) {
                $file = str_replace('\\', '/', dirname($file));
            }

            $this->root = rtrim($file, '/');
        }

        return $complete ? $this->domain() . $this->root : $this->root;
    }

    /**
     * Note: 获取URL访问根地址
     * Date: 2023-08-11
     * Time: 14:48
     * @return array|string
     */
    public function rootUrl()
    {
        $base = $this->url();
        $root = strpos($base, '.') ? ltrim(dirname($base), DIRECTORY_SEPARATOR) : $base;

        if ($root != '') {
            $root = '/' . ltrim($root, '/');
        }

        return $root;
    }

    /**
     * Note: 设置当前请求的pathinfo
     * Date: 2023-08-11
     * Time: 14:51
     * @param string $pathinfo 路径信息
     * @return $this
     */
    public function setPathinfo(string $pathinfo)
    {
        $this->pathinfo = $pathinfo;

        return $this;
    }

    /**
     * Note: 获取当前请求URL的pathinfo信息,包含后缀
     * Date: 2022-09-29
     * Time: 15:52
     * @return string
     */
    public function pathinfo()
    {
        if (is_null($this->pathinfo)) {
            if (isset($this->get[$this->varPathinfo])) {
                $pathinfo = $this->get[$this->varPathinfo];
                unset($this->get[$this->varPathinfo]);
                unset($_GET[$this->varPathinfo]);
            } elseif ($this->server('PATH_INFO')) {
                $pathinfo = $this->server('PATH_INFO');
            } elseif (strpos(PHP_SAPI, 'cli') !== false) {
                $pathinfo = strpos($this->server('REQUEST_URI'), '?') ? strstr($this->server('REQUEST_URI'), '?', TRUE) : $this->server('REQUEST_URI');
            }

            if (!isset($pathinfo)) {
                foreach ($this->pathinfoFetch as $type) {
                    if ($this->server($type)) {
                        $pathinfo = strpos($this->server($type), $this->server('SCRIPT_NAME')) === 0 ? substr($this->server($type), strlen($this->server('SCRIPT_NAME'))) : $this->server($type);
                        break;
                    }
                }
            }

            if (isset($pathinfo) && !empty($pathinfo)) {
                unset($this->get[$pathinfo], $this->request[$pathinfo]);
            }

            $this->pathinfo = empty($pathinfo) || $pathinfo == '/' ? '' : ltrim($pathinfo, '/');
        }

        return $this->pathinfo;
    }

    /**
     * Note: 设置请求类型
     * Date: 2023-08-11
     * Time: 15:20
     * @param string $method 请求类型
     * @return $this
     */
    public function setMethod(string $method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Note: 当前的请求类型
     * User: enna
     * Date: 2022-09-30
     * Time: 10:28
     * @param bool $origin 是否获取原始请求类型
     * @return string
     */
    public function method(bool $origin = false)
    {
        if ($origin) {
            return $this->server('REQUEST_METHOD') ?: 'GET';
        } elseif (!$this->method) {
            if (isset($this->post[$this->varMethod])) {
                $method = strtolower($this->post[$this->varMethod]);
                if (in_array($method, ['get', 'post', 'put', 'delete', 'patch'])) {
                    $this->method = strtoupper($method);
                    $this->{$method} = $this->post;
                } else {
                    $this->method = 'POST';
                }
                unset($this->post[$this->varMethod]);
            } elseif ($this->server('HTTP_X_HTTP_METHOD_OVERRIDE')) {
                $this->method = strtoupper($this->server('HTTP_X_HTTP_METHOD_OVERRIDE'));
            } else {
                $this->method = $this->server('REQUEST_METHOD') ?: 'GET';
            }
        }

        return $this->method;
    }

    /**
     * Note: 当前URL地址中scheme参数
     * Date: 2023-08-11
     * Time: 14:10
     * @return string
     */
    public function scheme()
    {
        return $this->isSsl() ? 'https' : 'http';
    }

    /**
     * Note: 获取客户端IP地址
     * Date: 2023-08-12
     * Time: 16:40
     * @return string
     */
    public function ip()
    {
        if (!empty($this->realIP)) {
            return $this->realIP;
        }

        $this->realIP = $this->server('REMOTE_ADDR', '');

        $proxyIp = $this->proxyServerIp;
        $proxyIpHeader = $this->proxyServerIpHeader;


        if (count($proxyIp) > 0 && count($proxyIpHeader) > 0) {
            // 从指定的HTTP头中依次尝试获取IP地址
            foreach ($proxyIpHeader as $header) {
                $tempIP = $this->server($header);

                if (empty($tempIP)) {
                    continue;
                }

                $tempIP = trim(explode(',', $tempIP)[0]);

                if (!$this->isValidIP($tempIP)) {
                    $tempIP = null;
                } else {
                    break;
                }
            }

            //检查REMOTE_ADDR是不是指定的前端代理服务器之一
            if (!empty($tempIP)) {
                $realIPBin = $this->ip2bin($this->realIP);

                foreach ($proxyIp as $ip) {
                    $serverIPElements = explode('/', $ip);
                    $serverIP = $serverIPElements[0];
                    $serverIPPrefix = $serverIPElements[1] ?? 128;
                    $serverIPBin = $this->ip2bin($serverIP);

                    if (strlen($realIPBin) !== strlen($serverIPBin)) {
                        continue;
                    }

                    if (strncmp($realIPBin, $serverIPBin, $serverIPPrefix) === 0) {
                        $this->realIP = $tempIP;
                        break;
                    }
                }
            }
        }

        if (!$this->isValidIP($this->realIP)) {
            $this->realIP = '0.0.0.0';
        }

        return $this->realIP;
    }

    /**
     * Note: 检测是否是合法的IP地址
     * Date: 2023-08-12
     * Time: 16:45
     * @param string $ip IP地址
     * @param string $type IP地址类型
     * @return bool
     */
    public function isValidIP(string $ip, string $type = '')
    {
        switch ($type) {
            case 'ipv4':
                $flag = FILTER_FLAG_IPV4;
                break;
            case 'ipv6':
                $flag = FILTER_FLAG_IPV6;
                break;
            default:
                $flag = 0;
                break;
        }

        return boolval(filter_var($ip, FILTER_VALIDATE_IP, $flag));
    }

    /**
     * Note: 将IP地址转换为二进制字符串
     * Date: 2023-08-12
     * Time: 17:13
     * @param string $ip IP地址
     * @return string
     */
    public function ip2bin(string $ip)
    {
        if ($this->isValidIP($ip, 'ipv6')) {
            $IPHex = str_split(bin2hex(inet_pton($ip)), 4);
            foreach ($IPHex as $key => $value) {
                $IPHex[$key] = intval($value, 16);
            }

            $IPBin = vsprintf('%016b%016b%016b%016b%016b%016b%016b%016b', $IPHex);
        } else {
            $IPHex = str_split(bin2hex(inet_pton($ip)), 2);
            foreach ($IPHex as $key => $value) {
                $IPHex[$key] = intval($value, 16);
            }

            $IPBin = vsprintf('%08b%08b%08b%08b', $IPHex);
        }

        return $IPBin;
    }

    /**
     * Note: 当前URL地址中的scheme参数
     * Date: 2023-08-11
     * Time: 18:33
     * @return string
     */
    public function query()
    {
        return $this->server('QUERY_STRING', '');
    }

    /**
     * Note: 当前请求URL地址中的port参数
     * Date: 2023-08-08
     * Time: 10:35
     * @return int
     */
    public function port()
    {
        return (int)($this->server('HTTT_X_FORWARDED_PORT') ?: $this->server('SERVER_PORT'));
    }

    /**
     * Note: 获取当前请求的端口
     * Date: 2023-08-12
     * Time: 17:33
     * @return int
     */
    public function remotePort()
    {
        return (int)$this->server('REMOTE_PORT', '');
    }

    /**
     * Note: 获取当前请求的协议
     * Date: 2023-08-12
     * Time: 17:33
     * @return string
     */
    public function protocol()
    {
        return $this->server('SERVER_PROTOCOL', '');
    }

    /**
     * Note: 获取content-type数据格式
     * Date: 2022-09-29
     * Time: 14:06
     * @return string
     */
    public function contentType()
    {
        $contentType = $this->header('Content-Type');

        if ($contentType) {
            if (strpos($contentType, ';')) {
                [$type] = explode(';', $contentType);
            } else {
                $type = $contentType;
            }

            return trim($type);
        }

        return '';
    }

    /**
     * Note: 获取当前请求的安全key
     * Date: 2023-08-12
     * Time: 17:35
     * @return bool|string
     */
    public function secureKey()
    {
        if (is_null($this->secureKey)) {
            $this->secureKey = uniqid('', true);
        }

        return $this->secureKey;
    }

    /**
     * Note: 当前URL的后缀
     * Date: 2022-09-29
     * Time: 18:03
     * @return string
     */
    public function ext()
    {
        return pathinfo($this->pathinfo(), PATHINFO_EXTENSION);
    }

    /**
     * Note: 获取请求的时间
     * Date: 2023-08-11
     * Time: 15:10
     * @param bool $float
     * @return array|mixed|string
     */
    public function time(bool $float = false)
    {
        return $float ? $this->server('REQUEST_TIME_FLOAT') : $this->server('REQUEST_TIME');
    }

    /**
     * Note: 请求的资源类型
     * Date: 2022-10-09
     * Time: 14:20
     * @return string
     */
    public function type()
    {
        $accept = $this->server('HTTP_ACCEPT');

        if (empty($accept)) {
            return '';
        }

        foreach ($this->mimeType as $key => $val) {
            $array = explode(',', $val);
            foreach ($array as $k => $v) {
                if (stristr($accept, $v)) {
                    return $key;
                }
            }
        }

        return '';
    }

    /**
     * Note: 设置资源类型
     * Date: 2023-08-11
     * Time: 15:14
     * @param string|array $type 类型名
     * @param string $val 资源类型
     * @return void
     */
    public function mimeType($type, $val = '')
    {
        if (is_array($type)) {
            $this->mimeType = array_merge($this->mimeType, $type);
        } else {
            $this->mimeType[$type] = $val;
        }
    }

    /**
     * Note: 检测是否使用手机访问
     * Date: 2023-08-12
     * Time: 17:26
     * @return bool
     */
    public function isMobile()
    {
        if ($this->server('HTTP_VIA') && stristr($this->server('HTTP_VIA', 'wap'))) {
            return true;
        } elseif ($this->server('HTTP_ACCEPT') && strpos(strtoupper($this->server('HTTP_ACCEPT')), 'VND.WAP.WML')) {
            return true;
        } elseif ($this->server('HTTP_X_WAP_PROFILE') || $this->server('HTTP_PROFILE')) {
            return true;
        } elseif ($this->server('HTTP_USER_AGENT') && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $this->server('HTTP_USER_AGENT'))) {
            return true;
        }

        return false;
    }

    /**
     * Note: 是否为GET请求
     * Date: 2023-08-03
     * Time: 16:47
     * @return bool
     */
    public function isGet()
    {
        return $this->method() == 'GET';
    }

    /**
     * Note: 是否为POST请求
     * Date: 2023-08-03
     * Time: 16:48
     * @return bool
     */
    public function isPost()
    {
        return $this->method() == 'POST';
    }

    /**
     * Note: 是否为PUT请求
     * Date: 2023-08-03
     * Time: 16:49
     * @return bool
     */
    public function isPut()
    {
        return $this->method() == 'PUT';
    }

    /**
     * Note: 是否为DELETE请求
     * Date: 2023-08-03
     * Time: 16:50
     * @return bool
     */
    public function isDelete()
    {
        return $this->method() == 'DELETE';
    }

    /**
     * Note: 是否为HEAD请求
     * Date: 2023-08-03
     * Time: 16:51
     * @return bool
     */
    public function isHead()
    {
        return $this->method() == 'HEAD';
    }

    /**
     * Note: 是否为PATCH请求
     * Date: 2023-08-03
     * Time: 16:51
     * @return bool
     */
    public function isPatch()
    {
        return $this->method() == 'PATCH';
    }

    /**
     * Note: 是否为OPTIONS请求
     * Date: 2023-08-03
     * Time: 16:52
     * @return bool
     */
    public function isOptions()
    {
        return $this->method() == 'OPTIONS';
    }

    /**
     * Note: 是否为cli
     * Date: 2023-08-03
     * Time: 16:53
     * @return bool
     */
    public function isCli()
    {
        return PHP_SAPI == 'cli';
    }

    /**
     * Note: 是否为cgi
     * Date: 2023-08-03
     * Time: 16:53
     * @return bool
     */
    public function cgi()
    {
        return strpos(PHP_SAPI, 'cgi') === 0;
    }

    /**
     * Note: 当前是否为JSON请求
     * Date: 2022-10-09
     * Time: 14:15
     * @return bool
     */
    public function isJson()
    {
        $acceptType = $this->type();

        return strpos($acceptType, 'json') !== false;
    }

    /**
     * Note: 当前是否为Ajax请求
     * Date: 2022-10-29
     * Time: 11:45
     * @param bool $ajax true:获取原始ajax请求
     * @return bool
     */
    public function isAjax(bool $ajax = false)
    {
        $value = $this->server('HTTP_X_REQUEST_WITH');
        $result = $value && strtolower($value) == 'xmlhttprequest' ? true : false;

        if ($ajax === true) {
            return $result;
        }

        return $this->param($this->varAjax) ? true : $result;
    }

    /**
     * Note: 当前是否Pjax请求
     * Date: 2023-08-14
     * Time: 14:45
     * @param bool $pjax true:获取原始pjax请求
     * @return bool
     */
    public function isPjax(bool $pjax = false)
    {
        $result = !empty($this->server('HTTP_X_PJAX')) ? true : false;

        if ($pjax === true) {
            return $result;
        }

        return $this->param($this->varPjax) ? true : $result;
    }

    /**
     * Note: 当前是否为ssl
     * Date: 2022-10-29
     * Time: 11:58
     * @return bool
     */
    public function isSsl()
    {
        if ($this->server('HTTPS') && ($this->server('HTTPS') == '1' || $this->server('HTTPS') == 'on')) {
            return true;
        } elseif ($this->server('REQUEST_SCHEME') == 'https') {
            return true;
        } elseif ($this->server('SERVER_PORT') == '443') {
            return true;
        } elseif ($this->server('HTTP_X_FORWARDED_PROTO') == 'https') {
            return true;
        } elseif ($this->httpsAgentName && $this->server($this->httpsAgentName)) {
            return true;
        }

        return false;
    }

    /**
     * Note: 生成token
     * Date: 2023-02-14
     * Time: 18:22
     * @param string $name token名称
     * @param string|callable $type hash类型
     * @return string
     */
    public function buildToken(string $name = '__token__', $type = 'md5')
    {
        $token = call_user_func($type, $this->server('REQUEST_TIME_FLOAT'));

        $this->session->set($name, $token);

        return $token;
    }

    /**
     * Note: 验证token令牌
     * Date: 2023-02-14
     * Time: 17:43
     * @param string $token token字段名
     * @param array $data 数据
     * @return bool
     */
    public function checkToken(string $token = '__token__', array $data = [])
    {
        if (in_array($this->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return true;
        }

        if (!$this->session->has($token)) {
            return false;
        }

        if ($this->header('X-CSRF-TOKEN') && $this->session->get($token) === $this->header('X-CSRF-TOKEN')) {
            $this->session->delete($token);
            return true;
        }

        if (empty($data)) {
            $data = $this->post();
        }

        if (isset($data[$token]) && $this->session->get($token) === $data[$token]) {
            $this->session->delete($token);
            return true;
        }

        $this->session->delete($token);
        return false;
    }

    /**
     * Note: 设置中间件传递的数据
     * Date: 2023-08-11
     * Time: 17:36
     * @param array $middleware 数据
     * @return $this
     */
    public function withMiddleware(array $middleware)
    {
        $this->middleware = array_merge($this->middleware, $middleware);

        return $this;
    }

    /**
     * Note: 设置get数据
     * Date: 2023-08-11
     * Time: 17:37
     * @param array $get 数据
     * @return $this
     */
    public function withGet(array $get)
    {
        $this->get = $get;

        return $this;
    }

    /**
     * Note: 设置post数据
     * Date: 2023-08-11
     * Time: 17:38
     * @param array $post 数据
     * @return $this
     */
    public function withPost(array $post)
    {
        $this->post = $post;

        return $this;
    }

    /**
     * Note: 设置cookie数据
     * Date: 2023-08-11
     * Time: 17:39
     * @param array $cookie 数据
     * @return $this
     */
    public function withCookie(array $cookie)
    {
        $this->cookie = $cookie;

        return $this;
    }

    /**
     * Note: 设置Session对象
     * Date: 2023-02-14
     * Time: 18:06
     * @param Session $session Session对象
     * @return $this
     */
    public function withSession(Session $session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Note: 设置server数据
     * Date: 2023-08-11
     * Time: 17:57
     * @param array $server 数据
     * @return $this
     */
    public function withServer(array $server)
    {
        $this->server = array_change_key_case($server, CASE_UPPER);

        return $this;
    }

    /**
     * Note: 设置header数据
     * Date: 2023-08-11
     * Time: 17:58
     * @param array $header 数据
     * @return $this
     */
    public function withHeader(array $header)
    {
        $this->header = array_change_key_case($header);

        return $this;
    }

    /**
     * Note: 设置env数据
     * Date: 2023-08-11
     * Time: 17:59
     * @param Env $env 数据
     * @return $this
     */
    public function withEnv(Env $env)
    {
        $this->env = $env;

        return $this;
    }

    /**
     * Note: 设置file数据
     * Date: 2023-08-11
     * Time: 18:00
     * @param array $files
     * @return $this
     */
    public function withFiles(array $files)
    {
        $this->file = $files;

        return $this;
    }

    /**
     * Note: 设置route变量
     * Date: 2023-08-11
     * Time: 17:24
     * @param array $route 数据
     * @return $this
     */
    public function withRoute(array $route)
    {
        $this->route = $route;

        return $this;
    }

    /**
     * Note: 设置php://input数据
     * Date: 2023-08-11
     * Time: 18:03
     * @param string $input RAW数据
     * @return $this
     */
    public function withInput(string $input)
    {
        $this->input = $input;
        if (!empty($input)) {
            $inputData = $this->getInputData($input);
            if (!empty($inputData)) {
                $this->post = $inputData;
                $this->put = $inputData;
            }
        }

        return $this;
    }

    /**
     * Note: 设置中间件传递数据
     * Date: 2023-07-07
     * Time: 14:20
     * @param string $name 参数名
     * @param mixed $value 值
     * @return void
     */
    public function __set(string $name, $value)
    {
        $this->middleware[$name] = $value;
    }

    /**
     * Note: 获取中间件传递数据的值
     * Date: 2023-07-07
     * Time: 14:22
     * @param string $name 参数名
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->middleware($name);
    }

    /**
     * Note: 检查中间件传递数据的值
     * Date: 2023-08-10
     * Time: 17:28
     * @param string $name 名称
     * @return bool
     */
    public function __isset(string $name)
    {
        return isset($this->middleware[$name]);
    }

    public function offsetUnset($offset)
    {
    }

    public function offsetExists($name)
    {
        return $this->has($name);
    }

    public function offsetGet($name)
    {
        return $this->param($name);
    }

    public function offsetSet($offset, $value)
    {
    }
}