<?php
declare(strict_types=1);

namespace Enna\Framework\Initializer;

use Enna\Framework\Service\ValidateService;
use Enna\Framework\App;

class RegisterService
{
    /**
     * 默认注册的系统服务
     * @var string[]
     */
    protected $services = [
        ValidateService::class,
    ];

    public function init(App $app)
    {
        $file = $app->getRootPath() . 'vendor/services.php';

        $services = $this->services;

        if (is_file($file)) {
            $services = array_merge($services, include $file);
        }

        foreach ($services as $service) {
            if (class_exists($service)) {
                $app->register($service);
            }
        }
    }
}