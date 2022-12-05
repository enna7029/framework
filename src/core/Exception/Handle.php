<?php
declare(strice_types=1);

namespace Enna\Framework\Exception;

use Enna\Framework\App;
use Enna\Framework\Request;
use Enna\Framework\Response;
use Throwable;
use Exception;

/**
 * 系统异常处理类
 * Class Handle
 * @package Enna\Framework\Exception
 */
class Handle
{
    /**
     * @var App
     */
    protected $app;

    /**
     * 忽略的报告
     * @var array
     */
    protected $ignoreReport = [];

    /**
     * 是否JSON格式
     * @var bool
     */
    protected $isJson = false;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * Note: 记录异常
     * User: enna
     * Date: 2022-09-20
     * Time: 15:21
     * @param Throwable $exception 异常对象
     */
    public function report(Throwable $exception)
    {
        if (!$this->isIgnoreReport($exception)) {
            if ($this->app->isDebug()) {
                $data = [
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                ];
                $log = "[{$data['code']}]{$data['message']}[{$data['file']}:{$data['line']}]";
            } else {
                $data = [
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                ];
                $log = "[{$data['code']}]{$data['message']}";
            }

            try {
                $this->app->log->record($log, 'error');
            } catch (Exception $e) {
            }
        }
    }

    /**
     * Note: 是否忽略异常
     * Date: 2022-09-20
     * Time: 15:24
     * @param Throwable $exception 异常对象
     * @return bool
     */
    protected function isIgnoreReport(Throwable $exception): bool
    {
        foreach ($this->ignoreReport as $class) {
            if ($exception instanceof $class) {
                return true;
            }
        }
        return false;
    }

    /**
     * Note: 渲染异常到HTTP Response中
     * Date: 2022-12-02
     * Time: 18:29
     * @param Request $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e)
    {
        $this->isJson = $request->isJson();
        if ($e instanceof HttpException) {

        } else {
            return $this->convertExceptionToResponse($e);
        }
    }

    /**
     * Note: 将异常转换为响应
     * Date: 2022-12-05
     * Time: 11:20
     * @param Throwable $exception
     * @return Response
     */
    protected function convertExceptionToResponse(Throwable $exception)
    {
        if (!$this->isJson) {
            $response = Response::create($this->convertExceptionToContent($exception));
        } else {
            $response = Response::create($this->convertExceptionToArray($exception), 'json');
        }

        if ($exception instanceof HttpException) {
            $statusCode = $exception->getStatusCode();
            $response->header($exception->getHeaders());
        }

        return $response->code($statusCode ?? 500);
    }

    /**
     * Note: 将异常转换为数组
     * Date: 2022-12-05
     * Time: 11:34
     * @param Throwable $exception
     * @return array
     */
    protected function convertExceptionToArray(Throwable $exception)
    {
        if ($this->app->isDebug()) {

        } else {
            $data = [
                'code' => $this->getCode($exception),
                'message' => $this->getMessage($exception)
            ];

            if (!$this->app->config->get('app.show_error_msg')) {
                $data['message'] = $this->app->config->get('app.error_message');
            }
        }

        return $data;
    }

    /**
     * Note: 将异常转换为字符串
     * Date: 2022-12-05
     * Time: 11:33
     * @param Throwable $exception
     * @return string|false
     */
    protected function convertExceptionToContent(Throwable $exception)
    {
        ob_start();
        $data = $this->convertExceptionToArray($exception);
        extract($data);
        include $this->app->config->get('app.exception_tmpl') ?: __DIR__ . '/../../tpl/default_exception.tpl';

        return ob_get_clean();
    }

    /**
     * Note: 获取错误编码
     * Date: 2022-12-05
     * Time: 11:59
     * @param Throwable $exception
     * @return int
     */
    protected function getCode(Throwable $exception)
    {
        $code = $this->getCode();

        if (!$code && $exception instanceof ErrorException) {
            $code = $exception->getSeverity();
        }

        return $code;
    }

    /**
     * Note: 获取错误信息
     * Date: 2022-12-05
     * Time: 12:03
     * @param Throwable $exception
     * @return string
     */
    protected function getMessage(Throwable $exception)
    {
        $message = $this->getMessage();

        return $message;
    }

}