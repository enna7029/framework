<?php
declare(strict_types=1);

namespace Enna\Framework\Facade;

use Enna\Framework\Facade;

/**
 * Class Event
 * @package Enna\Framework\Facade
 * @method static mixed trigger($event, $params = null, bool $once = false)
 */
class Event extends Facade
{
    protected static function getFacadeClass()
    {
        return 'event';
    }
}