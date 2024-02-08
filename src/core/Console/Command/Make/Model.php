<?php

namespace Enna\Framework\Console\Command\Make;

use Enna\Framework\Console\Command\Make;

class Model extends Make
{
    protected $type = 'Model';

    protected function configure()
    {
        parent::configure();
        $this->setName('make:model')
            ->setDescription('Create a new model class');
    }

    protected function getStub()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'Stubs' . DIRECTORY_SEPARATOR . 'model.stub';
    }

    protected function getNamespace(string $app)
    {
        return parent::getNamespace($app) . '\\model';
    }
}