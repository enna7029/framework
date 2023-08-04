<?php
declare(strict_types=1);

namespace Enna\Framework\Route\Dispatch;

use Enna\Framework\Route\Dispatch;

/**
 * 闭包调度器:用户访问闭包
 * Class Callback
 * @package Enna\Framework\Route\Dispatch
 */
class Callback extends Dispatch
{
    public function exec()
    {
        $vars = array_merge($this->request->param(), $this->param);

        return $this->app->invoke($this->dispatch, $vars);
    }
}