<?php
declare(strice_types=1);

namespace Enna\Framework\Exception;

use Enna\Framework\App;
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

    public function render()
    {

    }
}