<?php
declare(strict_types=1);

namespace Enna\Framework\Facade;

use Enna\Framework\Facade;

/**
 * Class Cookie
 * @package Enna\Framework\Facade
 */
class Cookie extends Facade
{
    protected static function getFacadeClass()
    {
        return 'cookie';
    }
}