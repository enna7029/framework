<?php
declare(strict_types=1);

namespace Enna\Framework\Initializer;

use Enna\Framework\App;

/**
 * 启动系统服务
 * Class BootService
 * @package Enna\Framework\Initializer
 */
class BootService
{
    public function init(App $app)
    {
        $app->boot();
    }
}