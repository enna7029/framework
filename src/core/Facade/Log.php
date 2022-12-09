<?php
declare(strict_types=1);

namespace Enna\Framework\Facade;

use Enna\Framework\Facade;

/**
 * Class Log
 * @method static string|null getDefaultDriver
 * @method static mixed|void resolveType
 * @method static mixed|void resolveConfig
 * @method static mixed|void getChannelConfig
 * @method static string|array|null getConfig
 * @method static Channel|ChannelSet channel
 * @method static \Enna\Framework\Log record($msg, string $type = 'info', array $context = [], bool $lazy = true)
 * @method static mixed|void createDriver
 */
class Log extends Facade
{
    protected static function getFacadeClass()
    {
        return 'log';
    }
}