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
     * Note: 获取中间件传递的参数
     * Date: 2023-07-07
     * Time: 14:23
     * @param string $name 参数名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function middleware(string $name, $default = null)
    {
        return $this->middleware[$name] ?? $default;
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

    /**
     * Note: 获取Cookie数据
     * Date: 2023-02-28
     * Time: 14:04
     * @param string $name cookie名称
     * @param mixed $default cookie默认值
     * @param string $fiter 过滤方法
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
     * Note: 设置全局过滤规则
     * Date: 2023-02-28
     * Time: 14:27
     * @param mixed $filter 过滤规则
     * @return $this|array
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
    public function getFilter(string $filter, $default)
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
        if (!in_array($type, ['param,get,post,put,delete,header,server,request,file,cookie,session,env,route'])) {
            return false;
        }

        $param = empty($this->$type) ? $this->$type() : $this->$type;

        foreach (explode(',', $param) as $val) {
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

    public function setDomain()
    {

    }

    public function domain()
    {

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