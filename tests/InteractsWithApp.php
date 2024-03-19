<?php

namespace Enna\Framework\tests;

use Mockery\MockInterface;
use Enna\Framework\App;
use Enna\Framework\Config;
use Enna\Framework\Container;
use Mockery;

trait InteractsWithApp
{
    /**
     * @var App|MockInterface
     */
    protected $app;

    /**
     * @var Config|MockInterface
     */
    protected $config;

    protected function prepareApp()
    {
        $this->app = Mockery::mock(App::class)->makePartial();
        Container::setInstance($this->app);
        $this->app->shouldReceive('make')->with(App::class)->andReturn($this->app);
        $this->app->shouldReceive('isDebug')->andReturnTrue();
        $this->config = Mockery::mock(Config::class)->makePartial();
        $this->config->shouldReceive('get')->with('app.show_error_msg')->andReturnTrue();
        $this->app->shouldReceive('get')->with('config')->andReturn($this->config);
        $this->app->shouldReceive('runningInConsole')->andReturn(false);
    }
}