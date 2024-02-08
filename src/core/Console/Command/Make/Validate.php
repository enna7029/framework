<?php

namespace Enna\Framework\Console\Command\Make;

use Enna\Framework\Console\Command\Make;

class Validate extends Make
{
    protected $type = 'Validate';

    protected function configure()
    {
        parent::configure();
        $this->setName('make:validate')
            ->setDescription('Create a new validate class');
    }

    protected function getStub()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'Stubs' . DIRECTORY_SEPARATOR . 'validate.stub';
    }

    protected function getNamespace(string $app)
    {
        return parent::getNamespace($app) . '\\validate';
    }
}