<?php

namespace Enna\Framework\tests;

use Enna\Framework\Cache;
use Enna\Framework\Config;
use Enna\Framework\Db;
use Enna\Framework\Event;
use Enna\Framework\Log;
use Mockery;
use PHPUnit\Framework\TestCase;

class DbTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testMake()
    {
        $this->assertTrue(true);
    }
}