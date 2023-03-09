<?php
declare(strict_types=1);

namespace Enna\Framework\Contract;

/**
 * Session驱动接口
 * Interface SessionHandlerInterface
 * @package Enna\Framework\Contract
 */
interface SessionHandlerInterface
{
    public function read(string $sessionId): string;

    public function delete(string $sessionId): bool;

    public function write(string $sessionId, string $data): bool;
}