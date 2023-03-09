<?php
declare(strict_types=1);

namespace Enna\Framework\Facade;

use Enna\Framework\Facade;

/**
 * Class Session
 * @package Enna\Framework\Facade
 */
class Session extends Facade
{
    protected static function getFacadeClass()
    {
        return 'session';
    }
}