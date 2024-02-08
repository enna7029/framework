<?php

namespace Enna\Framework\Console\Command\Make;

use Enna\Framework\Console\Command\Make;

class Subscribe extends Make
{
    protected $type = 'Subscribe';

    protected function configure()
    {
        parent::configure();
        $this->setName('make:subscribe')
            ->setDescription('Create a new subscribe class');
    }

    protected function getStub()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'Stubs' . DIRECTORY_SEPARATOR . 'subscribe.stub';
    }

    protected function getNamespace(string $app)
    {
        return parent::getNamespace($app) . '\\subscribe';
    }
}