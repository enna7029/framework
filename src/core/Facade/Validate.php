<?php
declare(strict_types=1);

namespace Enna\Framework\Facade;

use Enna\Framework\Facade;

class Validate extends Facade
{
    public static function getFacadeClass()
    {
        return 'validate';
    }
}