<?php
declare(strict_types=1);

namespace Enna\Framework\Initializer;

use Enna\Framework\Exception\ErrorException;
use Enna\Framework\App;
use Throwable;
use Enna\Framework\Exception\Handle;

class Error
{
    /**
     * 应用实例
     * @var App
     */
    protected $app;

    public function init(App $app)
    {
        $this->app = $app;
        error_reporting(E_ALL);
        set_error_handler([$this, 'appError']);
        set_exception_handler([$this, 'appException']);
        register_shutdown_function([$this, 'appShutdown']);
    }

    /**
     * Note: 自定义错误函数
     * Date: 2022-09-20
     * Time: 10:43
     * @param int $errno 错误级别
     * @param string $errstr 错误信息
     * @param string $errfile 错误文件
     * @param int $errline 错误行
     * @throws ErrorException
     */
    public function appError(int $errno, string $errstr, string $errfile = '', int $errline = 0): void
    {
        $exception = new ErrorException($errno, $errstr, $errfile, $errline);

        if (error_reporting() & $errno) {
            throw $exception;
        }
    }


    /**
     * Note: 自定义异常处理函数
     * Date: 2022-09-20
     * Time: 15:19
     * @param Throwable $e 异常对象
     */
    public function appException(Throwable $e)
    {
        $handler = $this->getExceptionHandler();
        $handler->report($e);

        if ($this->app->runningInConsole()) {

        } else {
            $handler->render($this->app->request, $e)->send();
            $this->app->log->save();
        }
    }

    /**
     * Note: 获取最后发生的错误
     * User: enna
     * Date: 2022-09-20
     * Time: 14:51
     */
    public function appShutdown()
    {
        if (!is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
            $exception = new ErrorException($error['type'], $error['message'], $error['file'], $error['line']);

            $this->appException($exception);
        }
    }

    /**
     * Note: 是否为不能由用户定义的函数来处理级别
     * Date: 2022-09-20
     * Time: 14:43
     * @param int $type
     * @return bool
     */
    public function isFatal(int $type)
    {
        return in_array($type, [E_ERROR, E_PARSE, E_COMPILE_ERROR, E_COMPILE_WARNING, E_CORE_ERROR, E_CORE_WARNING]);
    }

    /**
     * Note: 获取异常处理类
     * Date: 2022-09-20
     * Time: 15:39
     * @return Handle
     */
    protected function getExceptionHandler()
    {
        return $this->app->make(Handle::class);
    }
}