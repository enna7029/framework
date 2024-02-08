<?php

namespace Enna\Framework\Console\Command\Make;

use Enna\Framework\Console\Command\Make;

class Listener extends Make
{
    protected $type = 'Listener';

    protected function configure()
    {
        parent::configure();
        $this->setName('make:listener')
            ->setDescription('Create a new listener class');
    }

    protected function getStub()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'Stubs' . DIRECTORY_SEPARATOR . 'listener.stub';
    }

    protected function getNamespace(string $app)
    {
        return parent::getNamespace($app) . '\\listener';
    }
}