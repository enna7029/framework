<?php
declare (strict_types=1);

namespace Enna\Framework\Facade;

use Enna\Framework\Facade;

class App extends Facade
{
    protected static function getFacadeClass()
    {
        return 'app';
    }
}