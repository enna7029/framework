<?php
declare(strict_types=1);

namespace Enna\Framework\Contract;

/**
 * 日志驱动接口
 * Interface LogHandlerInterface
 * @package Enna\Framework\Contract
 */
interface LogHandlerInterface
{
    /**
     * Note: 日志写入接口
     * Date: 2022-12-08
     * Time: 10:00
     * @param array $log 日志信息
     * @return mixed
     */
    public function save(array $log);
}