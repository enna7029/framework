<?php
declare(strict_types=1);

namespace Enna\Framework\Facade;

use Enna\Framework\Facade;

class Event extends Facade
{
    protected static function getFacadeClass()
    {
        return 'event';
    }
}