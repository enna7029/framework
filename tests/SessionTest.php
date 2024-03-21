<?php

namespace Enna\Framework\tests;

use Enna\Framework\App;
use Enna\Framework\Config;
use Enna\Framework\Container;
use Enna\Framework\Contract\SessionHandlerInterface;
use Enna\Framework\Helper\Str;
use Enna\Framework\Session;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Mockery;
use Enna\Framework\Session\Driver\File;
use Enna\Framework\Session\Driver\Cache;
use Enna\Framework\Cache\Driver;

class SessionTest extends TestCase
{
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface |App
     */
    protected $app;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface |Config
     */
    protected $config;

    /**
     * @var Session | MockInterface
     */
    protected $session;

    protected $handler;

    protected function tearDown(): void
    {
        Mockery::close();
    }

    protected function setUp(): void
    {
        $this->app = Mockery::mock(App::class)->makePartial();
        Container::setInstance($this->app);

        $this->app->shouldReceive('make')->with(App::class)->andReturn($this->app);

        $this->config = Mockery::mock(Config::class)->makePartial();
        $this->app->shouldReceive('get')->with('config')->andReturn($this->config);

        $handlerClass = "\\Enna\\Framework\\Session\\Driver\\Test" . Str::random(10);
        $this->config->shouldReceive('get')->with('session.type', 'file')->andReturn($handlerClass);

        $this->session = new Session($this->app);

        $this->handler = Mockery::mock('overload:' . $handlerClass, SessionHandlerInterface::class);
    }

    public function testLoadData()
    {
        $data = [
            "bar" => 'foo',
        ];

        $id = md5(uniqid());

        $this->handler->shouldReceive('read')->once()->with($id)->andReturn(serialize($data));

        $this->session->setSessionId($id);
        $this->session->init();

        $this->assertEquals('foo', $this->session->get('bar'));
        $this->assertTrue($this->session->has('bar'));
        $this->assertFalse($this->session->has('foo'));

        $this->session->set('foo', 'bar');
        $this->assertTrue($this->session->has('foo'));

        $this->assertEquals('bar', $this->session->pull('foo'));
        $this->assertFalse($this->session->has('foo'));
    }

    public function testSave()
    {
        $id = md5(uniqid());

        $this->handler->shouldReceive('read')->once()->with($id)->andReturn("");

        $this->handler->shouldReceive('write')->once()->with($id, serialize([
            "bar" => 'foo',
        ]))->andReturnTrue();

        $this->session->setSessionId($id);
        $this->session->init();

        $this->session->set('bar', 'foo');

        $this->session->save();

        $this->assertEquals('foo', $this->session->get('bar'));
    }

    public function testFlash()
    {
        $this->session->flash('foo', 'bar');
        $this->session->flash('bar', 0);
        $this->session->flash('baz', true);

        $this->assertTrue($this->session->has('foo'));
        $this->assertEquals('bar', $this->session->get('foo'));
        $this->assertEquals(0, $this->session->get('bar'));
        $this->assertTrue($this->session->get('baz'));

        $this->session->clearFlashData();

        $this->assertTrue($this->session->has('foo'));
        $this->assertEquals('bar', $this->session->get('foo'));
        $this->assertEquals(0, $this->session->get('bar'));

        $this->session->clearFlashData();

        $this->assertFalse($this->session->has('foo'));
        $this->assertNull($this->session->get('foo'));

        $this->session->flash('foo', 'bar');
        $this->assertTrue($this->session->has('foo'));
        $this->session->clearFlashData();
        $this->session->reflash();

        $this->assertTrue($this->session->has('foo'));
    }

    public function testClear()
    {
        $this->session->set('bar', 'foo');
        $this->assertEquals('foo', $this->session->get('bar'));
        $this->session->clear();
        $this->assertFalse($this->session->has('foo'));
    }

    public function testSetName()
    {
        $this->session->setName('foo');
        $this->assertEquals('foo', $this->session->getName());
    }

    public function testDestroy()
    {
        $id = md5(uniqid());

        $this->handler->shouldReceive('read')->once()->with($id)->andReturn("");
        $this->handler->shouldReceive('delete')->once()->with($id)->andReturnTrue();

        $this->session->setSessionId($id);
        $this->session->init();

        $this->session->set('bar', 'foo');

        $this->session->destroy();

        $this->assertFalse($this->session->has('bar'));

        $this->assertNotEquals($id, $this->session->getSessionId());
    }

    public function testFileHandler()
    {
        $root = vfsStream::setup();

        vfsStream::newFile('bar')
            ->at($root)
            ->lastModified(time());

        vfsStream::newFile('bar')
            ->at(vfsStream::newDirectory("foo")->at($root))
            ->lastModified(100);

        $this->assertTrue($root->hasChild("bar"));
        $this->assertTrue($root->hasChild("foo/bar"));

        $handler = new TestFileHandle($this->app, [
            'path' => $root->url(),
            'gc_probability' => 1,
            'gc_divisor' => 1,
        ]);

        $id = md5(uniqid());
        $handler->write($id, "bar");

        $this->assertTrue($root->hasChild("session_{$id}"));

        $this->assertEquals("bar", $handler->read($id));

        $handler->delete($id);

        $this->assertFalse($root->hasChild("session_{$id}"));
    }

}

class TestFileHandle extends File
{
    protected function writeFile($path, $content): bool
    {
        return (bool)file_put_contents($path, $content);
    }
}