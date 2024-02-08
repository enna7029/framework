<?php
declare(strict_types=1);

namespace Enna\Framework\Service;

use Enna\Framework\Service;
use Enna\Orm\Model;

/**
 * 模型服务类
 * Class ModelService
 * @package Enna\Framework\Service
 */
class ModelService extends Service
{
    public function boot(): void
    {
        Model::setDb($this->app->db);
        Model::setEvent($this->app->event);
        Model::setInvoker([$this->app, 'invoke']);
        Model::maker(function (Model $model) {
            $config = $this->app->config;

            $autoWriteTimestamp = $model->getAutoWriteTimestamp();
            if (is_null($autoWriteTimestamp)) {
                $model->isAutoWriteTimestamp($config->get('database.auto_timestamp'), 'timestamp');
            }

            $dateFormat = $model->getDateFormat();
            if (is_null($dateFormat)) {
                $model->setDateFormat($config->get('database.datetime_format'), 'Y-m-d H:i:s');
            }

            $timeField = $config->get('database.datetime_field');
            if (!empty($timeField)) {
                [$createTime, $updateTime] = explode(',', $timeField);
                $model->setTimeField($createTime, $updateTime);
            }
        });
    }
}