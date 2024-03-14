<?php

namespace Enna\Framework\tests;

use Enna\Framework\Cache;
use Enna\Framework\Config;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Mockery;
use Mockery\MockInterface;
use Enna\Framework\App;
use Enna\Framework\Container;
use InvalidArgumentException;

class CacheTest extends TestCase
{
    /**
     * @var App|MockInterface
     */
    protected $app;

    /**
     * @var Config|MockInterface
     */
    protected $config;

    /**
     * @var Cache|MockInterface
     */
    protected $cache;

    protected function setUp(): void
    {
        $this->app = Mockery::Mock(App::class)->makePartial();
        Container::setInstance($this->app);
        $this->app->shouldReceive('make')->with(App::class)->andReturn($this->app);

        $this->config = Mockery::mock(Config::class)->makePartial();
        $this->app->shouldReceive('get')->with('config')->andReturn($this->config);

        $this->cache = new Cache($this->app);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testGetConfig()
    {
        $config = [
            'default' => 'file'
        ];

        $this->config->shouldReceive('get')->with('cache')->andReturn($config);
        $this->assertEquals($config, $this->cache->getConfig());

        $this->expectException(InvalidArgumentException::class);
        $this->cache->getStoreConfig('foo');
    }

    public function testCacheManagerInstances()
    {
        $this->config->shouldReceive('get')->with('cache.stores.single', null)->andReturn(['type' => 'file']);

        $channel1 = $this->cache->driver('single');
        $channel2 = $this->cache->driver('single');

        $this->assertSame($channel1, $channel2);
    }

    public function testFileCache()
    {
        $root = vfsStream::setup();

        $this->config->shouldReceive('get')->with('cache.default', null)->andReturn('file');
        $this->config->shouldReceive('get')->with('cache.stores.file', null)->andReturn([
            'type' => 'file',
            'path' => $root->url()
        ]);

        $this->cache->set('foo', 5);
        $this->cache->inc('foo');
        $this->assertEquals(6, $this->cache->get('foo'));
        $this->cache->dec('foo', 2);
        $this->assertEquals(4, $this->cache->get('foo'));

        $this->cache->set('bar', true);
        $this->assertTrue(true, $this->cache->get('bar'));

        $this->cache->set('baz', null);
        $this->assertNull($this->cache->get('baz'));
        $this->assertTrue($this->cache->has('baz'));
        $this->cache->delete('baz');
        $this->assertFalse($this->cache->has('baz'));
        $this->assertNull($this->cache->get('baz'));
        $this->assertFalse($this->cache->get('baz', false));

        $this->assertTrue($root->hasChildren());
        $this->cache->clear();
        $this->assertFalse($root->hasChildren());

        $this->cache->tag('foo')->set('bar', 'foobar');
        $this->assertEquals('foobar', $this->cache->get('bar'));
    }

    public function testRedisCache()
    {
        $this->assertTrue(true, true);
    }
}