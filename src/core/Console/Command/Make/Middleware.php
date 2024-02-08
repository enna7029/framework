<?php

namespace Enna\Framework\Console\Command\Make;

use Enna\Framework\Console\Command\Make;

class Middleware extends Make
{
    protected $type = 'Middleware';

    protected function configure()
    {
        parent::configure();
        $this->setName('make:middleware')
            ->setDescription('Create a new middleware class');
    }

    protected function getStub()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'Stubs' . DIRECTORY_SEPARATOR . 'middleware.stub';
    }

    protected function getNamespace(string $app)
    {
        return parent::getNamespace($app) . '\\middleware';
    }
}