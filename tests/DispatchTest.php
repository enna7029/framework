<?php

namespace Enna\Framework\tests;

use Enna\Framework\Request;
use Enna\Framework\Route\Dispatch;
use Enna\Framework\Route\Rule;
use PHPUnit\Framework\TestCase;
use Mockery;
use GuzzleHttp\Psr7\Response;

class  DispatchTest extends TestCase
{
    public function testPsr7Response()
    {
        $request = Mockery::mock(Request::class);
        $rule = Mockery::mock(Rule::class);
        $dispatch = new class($request, $rule, '') extends Dispatch {
            public function exec()
            {
                return new Response(200, ['framework' => ['en', 'enna'], 'psr' => 'psr-7'], '123');
            }
        };

        $response = $dispatch->run();

        $this->assertInstanceOf(\Enna\Framework\Response::class, $response);
        $this->assertEquals('123', $response->getContent());
        $this->assertEquals('en, enna', $response->getHeader('framework'));
        $this->assertEquals('psr-7', $response->getHeader('psr'));
    }
}