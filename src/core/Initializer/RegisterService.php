<?php
declare(strict_types=1);

namespace Enna\Framework\Initializer;

use Enna\Framework\Service\ValidateService;
use Enna\Framework\App;

class RegisterService
{
    protected $services = [
        ValidateService::class,
    ];

    public function init(App $app)
    {
        $services = $this->services;

        foreach ($services as $service) {
            if (class_exists($service)) {
                $app->register($service);
            }
        }
    }
}