<?php

namespace Enna\Framework\Console\Command\Make;

use Enna\Framework\Console\Command\Make;

class Service extends Make
{
    protected $type = 'Service';

    protected function configure()
    {
        parent::configure();
        $this->setName('make:service')
            ->setDescription('Create a new Service class');
    }

    protected function getStub()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'Stubs' . DIRECTORY_SEPARATOR . 'service.stub';
    }

    protected function getNamespace(string $app)
    {
        return parent::getNamespace($app) . '\\service';
    }
}