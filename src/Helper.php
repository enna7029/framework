<?php

use Enna\Framework\Container;
use Enna\Framework\App;

if (!function_exists('app')) {
    function app(string $name = '', array $args = [], bool $newInstance = false)
    {
        return Container::getInstance()->make($name ?: App::class, $args, $newInstance);
    }
}