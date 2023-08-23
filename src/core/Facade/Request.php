<?php
declare(strict_types=1);

namespace Enna\Framework\Facade;

use Enna\Framework\Env;
use Enna\Framework\Facade;
use Enna\Framework\File\UploadFile;
use Enna\Framework\Route\Rule;
use Enna\Framework\Session;

/**
 * Class Request
 * @package Enna\Framework\Facade
 * @method static mixed param(string|array $name = '', mixed $default = null, string|array $filter = '') 获取当前请求参数
 * @method static mixed all(string|array $name = '', string|array $filter = '') 获取包含文件在内的请求参数
 * @method static mixed post(string|array $name = '', mixed $default = null, string|array $filter = '') 获取POST请求参数
 * @method static mixed get(string|array $name = '', mixed $default = null, string|array $filter = '') 获取GET请求参数
 * @method static mixed middleware(mixed $name, mixed $default = null) 获取中间件传递的参数
 * @method static mixed put(string|array $name = '', mixed $default = null, string|array $filter = '') 获取PUT参数
 * @method static mixed delete($name = '', $default = null, $filter = '')
 * @method static mixed patch($name = '', $default = null, $filter = '')
 * @method static mixed request($name = '', $default = null, $filter = '')
 * @method static string|array header(string $name = '', string $default = '')
 * @method static mixed input(array $data = [], $name = '', $default = null, $filter = '')
 * @method static array only(array $name, $data = 'param', $filter = '')
 * @method static mixed except(array $name, string $type = 'param')
 * @method static mixed env(string $name = '', string $default = null)
 * @method static mixed session(string $name = '', $default = null)
 * @method static mixed cookie(string $name = '', $default = null, $filter = '')
 * @method static mixed server(string $name = '', string $default = '')
 * @method static null|array|UploadFile file(string $name = '') 获取上传的文件信息
 * @method static Enna\Framework\Request|mixed filter($filter = null) 设置全局过滤规则
 * @method static Enna\Framework\Request setRule(Rule $rule) 设置路由规则对象
 * @method static Rule|null rule() 获取路由规则对象
 * @method static Enna\Framework\Request setRoute(array $route) 设置路由变量
 * @method static route($name = '', $default = null, $filter = '') 获取路由参数
 * @method static setController(string $controller) 设置当前控制器
 * @method static controller(bool $convert = false) 获取当前的控制器名
 * @method static setAction(string $action) 设置当前操作
 * @method static action(bool $convert = false) 获取当前操作名
 * @method static getContent() 设置或者获取当前请求的content
 * @method static getInput() 获取当前请求的php://input
 * @method static has(string $name, string $type = 'param', bool $checkEmpty = false) 检查是否存在某个请求参数
 * @method static filterValue(&$value, $name, $filters) 获取过滤后的值
 * @method static rootDomain() 获取根域名
 * @method static setDomain(string $domain) 设置当前包含协议的域名
 * @method static domain(bool $port = false) 获取当前包含协议的域名
 * @method static setSubDomain(string $domain) 设置当前子域名
 * @method static subDomain() 获取当前子域名
 * @method static setPanDomain(string $domain) 设置当前泛域名的值
 * @method static panDomain() 获取当前泛域名值
 * @method static setHost(string $host) 设置当前请求的host(包含端口)
 * @method static host(bool $strict = false) 当前请求的host
 * @method static setUrl(string $url) 设置当前完整URL
 * @method static url(bool $complete = false) 获取当前完整URL
 * @method static setBaseUrl(string $url) 设置当前URL
 * @method static baseUrl(bool $complete = false) 获取当前URL
 * @method static baseFile(bool $complete = true) 获取当前执行的文件
 * @method static setRoot(string $url) 设置URL访问根地址
 * @method static root(bool $complete = false) 获取URL
 * @method static rootUrl() 获取URL访问根地址
 * @method static setPathinfo(string $pathinfo) 设置当前请求的pathinfo
 * @method static pathinfo() 获取当前请求URL的pathinfo信息, 包含后缀
 * @method static setMethod(string $method) 设置请求类型
 * @method static method(bool $origin = false) 当前的请求类型
 * @method static scheme() 当前URL地址中scheme参数
 * @method static ip() 获取客户端IP地址
 * @method static isValidIP(string $ip, string $type = '') 检测是否是合法的IP地址
 * @method static ip2bin(string $ip) 将IP地址转换为二进制字符串
 * @method static query() 当前URL地址中的scheme参数
 * @method static port() 当前请求URL地址中的port参数
 * @method static remotePort() 获取当前请求的端口
 * @method static protocol() 获取当前请求的协议
 * @method static contentType() 获取content-type数据格式
 * @method static secureKey() 获取当前请求的安全key
 * @method static ext() 当前URL的后缀
 * @method static time(bool $float = false) 获取请求的时间
 * @method static type() 请求的资源类型
 * @method static mimeType($type, $val = '') 设置资源类型
 * @method static isMobile() 检测是否使用手机访问
 * @method static isGet() 是否为GET请求
 * @method static isPost() 是否为POST请求
 * @method static isPut() 是否为PUT请求
 * @method static isDelete() 是否为DELETE请求
 * @method static isHead() 是否为HEAD请求
 * @method static isPatch() 是否为PATCH请求
 * @method static isOptions() 是否为OPTIONS请求
 * @method static isCli() 是否为cli
 * @method static cgi() 是否为cgi
 * @method static isJson() 当前是否为JSON请求
 * @method static isAjax(bool $ajax = false) 当前是否为Ajax请求
 * @method static isPjax(bool $pjax = false) 当前是否Pjax请求
 * @method static isSsl() 当前是否为ssl
 * @method static buildToken(string $name = '__token__', $type = 'md5') 生成token
 * @method static checkToken(string $token = '__token__', array $data = []) 验证token令牌
 * @method static withMiddleware(array $middleware) 设置中间件传递的数据
 * @method static withGet(array $get) 设置get数据
 * @method static withPost(array $post) 设置post数据
 * @method static withCookie(array $cookie) 设置cookie数据
 * @method static withSession(Session $session) 设置Session对象
 * @method static withServer(array $server) 设置server数据
 * @method static withHeader(array $header) 设置header数据
 * @method static withEnv(Env $env) 设置env数据
 * @method static withFiles(array $files) 设置file数据
 * @method static withRoute(array $route) 设置route变量
 * @method static withInput(string $input) 设置php://input数据
 * @method static mixed __set(string $name, $value) 设置中间件传递数据
 * @method static mixed __get(string $name) 获取中间件传递数据的值
 * @method static bool __isset(string $name) 检查中间件传递数据的值
 * @method static mixed offsetUnset($offset)
 * @method static bool offsetExists($name)
 * @method static mixed offsetGet($name)
 * @method static mixed offsetSet($offset, $value)
 */
class Request extends Facade
{
    protected static function getFacadeClass()
    {
        return 'request';
    }
}