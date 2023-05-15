<?php
declare(strict_types=1);

namespace Enna\Framework\Service;

use Enna\Framework\Service;
use Enna\Framework\Validate;

/**
 * 验证服务类
 * Class ValidateService
 * @package Enna\Framework\Service
 */
class ValidateService extends Service
{
    public function boot()
    {
        Validate::maker(function (Validate $validate) {
            $validate->setLang($this->app->lang);
            $validate->setDb($this->app->db);
            $validate->setRequest($this->app->request);
        });
    }
}