<?php
declare(strict_types=1);

namespace Enna\Framework\Facade;

use Enna\Framework\Facade;
use Enna\Framework\File\UploadFile;

/**
 * Class Request
 * @method static null|array|UploadFile file(string $name = '') 获取上传的文件信息
 * @package Enna\Framework\Facade
 */
class Request extends Facade
{
    protected static function getFacadeClass()
    {
        return 'request';
    }
}