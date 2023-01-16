<?php
declare(strict_types=1);

namespace Enna\Framework;

use ArrayAccess;
use Enna\Framework\Route\Rule;
use Enna\Framework\File\UploadFile;

class Request implements ArrayAccess
{
    /**
     * PATHINFO变量名,用于兼容模式
     * @var string
     */
    protected $varPathinfo = 's';

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
    protected $put = [];

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
     * COOKIE参数
     * @var array
     */
    protected $cookie = [];

    /**
     * PATH_INFO
     * @var string
     */
    protected $pathinfo;

    /**
     * 当前host(含端口)
     * @var string
     */
    protected $host;

    /**
     * 根域名
     * @var string
     */
    protected $rootDomain;

    /**
     * 子域名
     * @var string
     */
    protected $subDomain;

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
     * Env对象
     * @var Env
     */
    protected $env;

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
     * Note: 设置或获取当前header信息
     * Date: 2022-09-29
     * Time: 14:12
     * @param string $name header名称
     * @param string $default 默认值
     * @return string|array|null
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
     * Note: 当前的请求方法
     * User: enna
     * Date: 2022-09-30
     * Time: 10:28
     * @return string
     */
    public function method()
    {
        if (!$this->method) {
            if ($this->server('HTTP_X_HTTP_METHOD_OVERRIDE')) {
                $this->method = $this->server('HTTP_X_HTTP_METHOD_OVERRIDE');
            } else {
                $this->method = $this->server('REQUEST_METHOD') ?: 'GET';
            }
        }

        return $this->method;
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
            }

            $this->pathinfo = empty($pathinfo) || $pathinfo == '/' ? '' : ltrim($pathinfo, '/');
        }

        return $this->pathinfo;
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
     * Note: 获取当前请求参数
     * Date: 2022-09-30
     * Time: 15:59
     * @param string $name 变量名
     * @param null $default 默认值
     * @param string $filter 过滤方法
     * @return mixed
     */
    public function param($name = '', $default = null, $filter = '')
    {
        if (empty($this->mergeParam)) {
            $method = $this->method();

            // 自动获取请求变量
            switch ($method) {
                case 'POST':
                    $vars = $this->post();
                    break;
                case 'PUT':
                case 'DELETE':
                case 'PATCH':
                    $vars = $this->put();
                    break;
                default:
                    $vars = [];
            }

            $this->param = array_merge($this->param, $this->get(), $vars, $this->route());

            $this->mergeParam = true;
        }

        return $this->input($this->param, $name, $default, $filter);
    }

    /**
     * Note: 获取POST请求参数
     * Date: 2022-09-30
     * Time: 16:13
     * @param string $name 变量名
     * @param null $default 默认值
     * @param string $filter 过滤方法
     */
    public function post($name = '', $default = null, $filter = '')
    {
        return $this->input($this->post, $name, $default, $filter);
    }

    /**
     * Note: 获取GET请求参数
     * Date: 2022-09-30
     * Time: 17:51
     * @param string $name 变量名
     * @param null $default 默认值
     * @param string $filter 过滤方法
     */
    public function get($name = '', $default = null, $filter = '')
    {
        return $this->input($this->get, $name, $default, $filter);
    }

    /**
     * Note: 获取PUT参数
     * Date: 2022-11-16
     * Time: 14:43
     * @param string $name 变量名
     * @param null $default 默认值
     * @param string $filter 过滤方法
     * @return array|mixed|null
     */
    public function put($name = '', $default = null, $filter = '')
    {
        return $this->input($this->put, $name, $default, $filter);
    }

    /**
     * Note: 获取上传的文件信息
     * Date: 2023-01-06
     * Time: 10:30
     * @param string $name 名称
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
            if (is_array($file)) {
                $item = [];

                $keys = array_keys($file);
                $count = count($file['name']);
                for ($i = 0; $i < $count; $i++) {
                    if ($file['error'][$i] > 0) {
                        if ($name == $key) {
                            $this->throwUploadError($file['error'][$i]);
                        } else {
                            continue;
                        }
                    }

                    foreach ($keys as $temp_key) {
                        $temp[$temp_key] = $file[$temp_key][$i];
                    }

                    $item[] = new UploadFile($temp['tmp_name'], $temp['name'], $temp['type'], $temp['error']);
                }

                $array[$key] = $item;
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

    public function cookie()
    {

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
     * @param string $name 变量名
     * @param null $default 默认值
     * @param string $filter 过滤方法
     */
    public function route($name = '', $default = null, $filter = '')
    {
        return $this->input($this->route, $name, $default, $filter);
    }


    /**
     * Note: 获取变量,支持默认值和过滤
     * Date: 2022-09-30
     * Time: 16:15
     * @param array $data 数据
     * @param string $name 字段名
     * @param null $default 默认值
     * @param string $filter 过滤函数
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
     * Note: 获取数据
     * Date: 2022-09-30
     * Time: 16:28
     * @param array $data
     * @param string $name
     * @param null $default
     * @return mixed
     */
    protected function getData(array $data, string $name, $default = null)
    {
        if (isset($data[$name])) {
            $data = $data[$name];
        } else {
            $data = $default;
        }
        return $data;
    }

    /**
     * Note: 过滤数据
     * Date: 2022-09-30
     * Time: 17:20
     * @param array $data 数据
     * @param string $filter 过滤器
     * @param string $name 字段名
     * @param null $default 默认值
     * @return mixed
     */
    protected function filterData(array $data, $filter = '', $name = '', $default = null)
    {
        //解析过滤器
        if (!empty($filter)) {
            if (strpos($filter, ',')) {
                $filter = explode(',', $filter);
            } else {
                $filter = (array)$filter;
            }
        } else {
            $filter = [];
        }
        $filter[] = $default;

        if (is_array($data)) {
            array_walk_recursive($data, [$this, 'filterValue'], $filter);
        } else {
            $this->filterValue($data, $name, $filter);
        }

        return $data;
    }

    /**
     * Note: 递归过滤给定的值
     * Date: 2022-09-30
     * Time: 17:31
     * @param mixed $value 键值
     * @param mixed $key 键名
     * @param array $filters 过滤方法+默认值
     */
    protected function filterValue(&$value, $key, $filters)
    {
        $default = array_pop($filter);

        foreach ($filters as $filter) {
            if (is_callable($filters)) {
                if (is_null($value)) {
                    continue;
                }
            }

            $value = call_user_func($filter, $value);
        }

        if (empty($value)) {
            $value = $default;
        }

        return $value;

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
     * @return bool
     */
    public function isAjax()
    {
        $value = $this->server('HTTP_X_REQUEST_WITH');
        $result = $value && strtolower($value) == 'xmlhttprequest' ? true : false;

        return $result;
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
        }

        return false;
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
     * @return string
     */
    public function action()
    {
        return $this->action ?: '';
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
     * Note: 获取根域名
     * Date: 2022-10-28
     * Time: 17:15
     */
    public function rootDomain()
    {
        if (is_null($this->rootDomain)) {
            $item = explode(',', $this->host());
            $count = count($item);
            $root = $count > 1 ? $item[$count - 2] . '.' . $item[$count - 1] : $item[0];
        }

        return $root;
    }

    public function offsetUnset($offset)
    {
        // TODO: Implement offsetUnset() method.
    }

    public function offsetExists($offset)
    {
        // TODO: Implement offsetExists() method.
    }

    public function offsetGet($offset)
    {
        // TODO: Implement offsetGet() method.
    }

    public function offsetSet($offset, $value)
    {
        // TODO: Implement offsetSet() method.
    }
}