<?php
declare(strict_types=1);

namespace Enna\Framework;

/**
 * Class Service
 * @package Enna\Framework
 * @method void register()
 * @method void boot()
 */
abstract class Service
{
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }
}