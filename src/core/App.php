<?php
declare(strict_types=1);

namespace Enna\Framework;

use Enna\Framework\Event\AppInit;
use Enna\Framework\Initializer\Error;
use Enna\Framework\Initializer\BootService;
use Enna\Framework\Initializer\RegisterService;

/**
 * App基础类
 * @property App $app
 * @property Http $http
 * @property Request $request
 * @property Response $response
 * @property Env $env
 * @property Config $config
 * @property Event $event
 * @property Lang $lang
 * @property Log $log
 * @property Middleware $middleware
 * @property Route $route
 * @property Cache $cache
 * @property File $file
 * @property Validate $validate
 * @property Cookie $cookie
 * @property Session $session
 * @property Db $db
 */
class App extends Container
{
    const VERSION = '1.0.0';

    /**
     * 调试模式
     * @var bool
     */
    protected $appDebug = false;

    /**
     * 核心目录
     * @var string
     */
    protected $corePath;

    /**
     * 根目录
     * @var string
     */
    protected $rootPath;

    /**
     * 应用目录
     * @var string
     */
    protected $appPath;

    /**
     * 运行时目录
     * @var string
     */
    protected $runtimePath;

    /**
     * 应用开始时间
     * @var float
     */
    protected $beginTime;

    /**
     * 应用开始内存使用量
     * @var int
     */
    protected $beginMem;

    /**
     * 环境变量名称
     * @var string
     */
    protected $envName = '';

    /**
     * 注册的系统服务
     * @var array
     */
    protected $services = [];

    /**
     * 当前应用类库命名空间
     * @var string
     */
    protected $namespace = 'app';

    /**
     * PHP文件后缀
     * @var string
     */
    protected $configExt = '.php';

    /**
     * 初始化
     * @var bool
     */
    protected $initialized = false;

    /**
     * 应用初始化器
     * @var array
     */
    protected $initializers = [
        Error::class,
        RegisterService::class,
        BootService::class,
    ];

    /**
     * 容器绑定标识
     * @var array
     */
    protected $bind = [
        'app' => App::class,
        'http' => Http::class,
        'request' => Request::class,
        'response' => Response::class,
        'env' => Env::class,
        'config' => Config::class,
        'event' => Event::class,
        'lang' => Lang::class,
        'log' => Log::class,
        'middleware' => Middleware::class,
        'route' => Route::class,
        'cache' => Cache::class,
        'filesystem' => Filesystem::class,
        'validate' => Validate::class,
        'lang' => Lang::class,
        'cookie' => Cookie::class,
        'session' => Session::class,
        'db' => Db::class,
        'Enna\Orn\DbManager' => Db::class,
    ];

    /**
     * App constructor.
     * @param string $rootPath 应用根目录
     */
    public function __construct(string $rootPath = '')
    {
        $this->corePath = realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR;
        $this->rootPath = $rootPath ? rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : dirname($this->corePath, 4) . DIRECTORY_SEPARATOR;
        $this->appPath = $this->rootPath . 'app' . DIRECTORY_SEPARATOR;
        $this->runtimePath = $this->rootPath . 'runtime' . DIRECTORY_SEPARATOR;

        if (is_file($this->appPath . 'provider.php')) {
            $this->bind(include $this->appPath . 'provider.php');
        }

        static::setInstance($this);

        $this->instance('app', $this);
        $this->instance('container', $this);
    }

    /**
     * Note: 是否初始化
     * Date: 2022-09-20
     * Time: 10:31
     * @return bool
     */
    public function initialized()
    {
        return $this->initialized;
    }

    /**
     * Note: 初始化应用
     * Date: 2022-09-20
     * Time: 10:30
     * @return $this
     */
    public function initialize()
    {
        $this->initialized = true;

        $this->beginTime = microtime(true);
        $this->beginMem = memory_get_usage();

        //加载环境变量
        $this->loadEnv($this->envName);

        //设置调试模式
        $this->debugInit();

        //加载全局初始化文件
        $this->load();

        //加载框架默认语言包
        $default_lang = $this->lang->defaultLang();
        $this->lang->load($this->corePath . 'lang' . DIRECTORY_SEPARATOR . $default_lang . '.php');

        //加载应用默认语言包
        $this->loadLangPack($default_lang);

        //监听AppInit
        $this->event->trigger(AppInit::class);

        date_default_timezone_set($this->config->get('app.default_timezone', 'Asia/Shanghai'));

        //初始化
        foreach ($this->initializers as $initializer) {
            $this->make($initializer)->init($this);
        }
        return $this;
    }

    /**
     * Note: 加载环境变量定义
     * Date: 2022-10-13
     * Time: 18:18
     * @param string $envName 环境表示
     * @return void
     */
    protected function loadEnv($envName = '')
    {
        $envFile = $envName ? $this->rootPath . '.env.' . $envName : $this->rootPath . '.env';

        if ($envFile) {
            $this->env->load($envFile);
        }
    }

    /**
     * Note: 调试模式设置
     * Date: 2023-06-21
     * Time: 17:52
     */
    protected function debugInit()
    {
        if (!$this->appDebug) {
            $this->appDebug = $this->env->get('app_debug') ? true : false;
            ini_set('display_errors', 'Off');
        }

        if (!$this->runningInConsole()) {
            if (ob_get_level() > 0) {
                $output = ob_get_clean();
            }
            ob_start();
            if (!empty($output)) {
                echo $output;
            }
        }
    }

    /**
     * Note: 加载应用文件和配置
     * Date: 2023-06-21
     * Time: 18:03
     * @return void
     */
    protected function load()
    {
        $appPath = $this->getAppPath();

        //函数
        if (is_file($appPath . 'common.php')) {
            include_once $appPath . 'common.php';
        }

        include_once $this->corePath . 'Helper.php';

        //配置
        $configPath = $this->getConfigPath();
        $this->configExt = $this->env->get('config_ext', '.php');
        $files = [];
        if (is_dir($configPath)) {
            $files = glob($configPath . '*' . $this->configExt);
        }
        foreach ($files as $file) {
            $this->config->load($file, pathinfo($file, PATHINFO_FILENAME));
        }

        //事件
        if (is_file($appPath . 'event.php')) {
            $this->loadEvent(include $appPath . 'event.php');
        }

        //服务
        if (is_file($appPath . 'service.php')) {
            $services = include $appPath . 'service.php';
            foreach ($services as $service) {
                $this->register($service);
            }
        }
    }

    /**
     * Note: 引导服务boot
     * Date: 2022-10-11
     * Time: 15:25
     * @return void
     */
    public function boot()
    {
        array_walk($this->services, function ($service) {
            $this->bootService($service);
        });
    }

    /**
     * Note: 执行服务
     * Date: 2022-10-11
     * Time: 15:27
     * @param Service $service 服务
     * @return mixed
     */
    public function bootService($service)
    {
        if (method_exists($service, 'boot')) {
            return $this->invoke([$service, 'boot']);
        }
    }

    /**
     * Note: 加载语言包
     * Date: 2022-09-17
     * Time: 15:34
     * @param string $lang 语言
     * @return void
     */
    public function loadLangPack($lang)
    {
        if (empty($lang)) {
            return;
        }

        //加载应用语言包
        $files = glob($this->appPath . 'lang' . DIRECTORY_SEPARATOR . $lang . '.*');
        $this->lang->load($files);

        //加载扩展语言包
        $list = $this->config->get('lang.extend_list', []);
        if (isset($list[$lang])) {
            $this->lang->load($list[$lang]);
        }
    }

    /**
     * Note: 注册事件
     * Date: 2022-09-16
     * Time: 18:13
     * @param array $event
     * @return void
     */
    public function loadEvent(array $event)
    {
        if (isset($event['bind'])) {
            $this->event->bind($event['bind']);
        }

        if (isset($event['listen'])) {
            $this->event->listenEvents($event['listen']);
        }

        if (isset($event['subscribe'])) {
            $this->event->subscribe($event['subscribe']);
        }
    }

    /**
     * Note: 注册服务
     * Date: 2022-09-17
     * Time: 14:47
     * @param Service|string $service 服务
     * @param bool $force 强制重新注册
     * @return Service|void
     */
    public function register($service, bool $force = false)
    {
        //是否注册
        $registered = $this->getService($service);

        if ($registered && !$force) {
            return $registered;
        }

        if (is_string($service)) {
            $service = new $service($this);
        }

        if (method_exists($service, 'register')) {
            $service->register();
        }

        if (property_exists($service, 'bind')) {
            $this->bind($service->bind);
        }

        $this->services[] = $service;
    }

    /**
     * Note: 解析应用类的类名
     * Date: 2022-10-09
     * Time: 18:31
     * @param string $layer
     * @param string $name
     * @return string
     */
    public function parseClass(string $layer, string $name)
    {
        return $this->namespace . '\\' . $layer . '\\' . $name;

    }

    /**
     * Note: 获取服务
     * Date: 2022-09-17
     * Time: 15:07
     * @param mixed $service 服务
     */
    public function getService($service)
    {
        $name = is_string($service) ? $service : get_class($service);

        return array_values(array_filter($this->services, function ($value) use ($name) {
                return $value instanceof $name;
            }, ARRAY_FILTER_USE_BOTH))[0] ?? null;
    }

    /**
     * Note: 开启调试模式
     * Date: 2022-10-12
     * Time: 17:35
     * @param bool $debug 开启调试模式
     * @return $this
     */
    public function debug(bool $debug = true)
    {
        $this->appDebug = $debug;

        return $this;
    }

    /**
     * Note: 是否debug模式
     * Date: 2022-09-20
     * Time: 15:27
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->appDebug;
    }

    /**
     * Note: 设置命名空间
     * Date: 2022-10-12
     * Time: 17:38
     * @param string $namespace 命名空间
     * @return $this
     */
    public function setNamespace(string $namespace)
    {
        $this->namespace = $namespace;

        return $this;
    }


    /**
     * Note: 获取命名空间
     * Date: 2022-10-12
     * Time: 17:40
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Note: 获取框架版本
     * Date: 2022-10-12
     * Time: 17:43
     * @return string
     */
    public function version()
    {
        return static::VERSION;
    }

    /**
     * Note: 是否在命令行下运行
     * Date: 2022-09-20
     * Time: 17:03
     * @return bool
     */
    public function runningInConsole(): bool
    {
        return php_sapi_name() === 'cli' || php_sapi_name() === 'phpdbg';
    }

    /**
     * Note: 获取应用初识内存占用
     * Date: 2022-10-13
     * Time: 18:11
     * @return int
     */
    public function getBeginMem()
    {
        return $this->beginMem;
    }

    /**
     * Note: 获取应用开始时间
     * Date: 2022-10-13
     * Time: 18:12
     * @return float
     */
    public function getBeginTime()
    {
        return $this->beginTime;
    }

    /**
     * Note: 获取根目录
     * Date: 2022-09-16
     * Time: 17:05
     * @return string
     */
    public function getRootPath()
    {
        return $this->rootPath;
    }

    /**
     * Note: 获取应用目录
     * Date: 2022-09-16
     * Time: 17:05
     * @return string
     */
    public function getAppPath()
    {
        return $this->appPath;
    }

    /**
     * Note: 获取配置目录
     * Date: 2022-09-16
     * Time: 17:08
     */
    public function getConfigPath()
    {
        return $this->rootPath . 'config' . DIRECTORY_SEPARATOR;
    }

    /**
     * Note: 获取配置后缀
     * Date: 2023-07-05
     * Time: 18:43
     * @return string
     */
    public function getConfigExt()
    {
        return $this->configExt;
    }

    /**
     * Note: 获取核心目录
     * Date: 2022-09-17
     * Time: 16:05
     * @return string
     */
    public function getCorePath()
    {
        return $this->corePath;
    }

    /**
     * Note: 获取运行时目录
     * Date: 2022-10-12
     * Time: 18:29
     * @return string
     */
    public function getRuntimePath()
    {
        return $this->runtimePath;
    }
}

