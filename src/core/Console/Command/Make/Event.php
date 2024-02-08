<?php

namespace Enna\Framework\Console\Command\Make;

use Enna\Framework\Console\Command\Make;

class Event extends Make
{
    protected $type = 'Event';

    protected function configure()
    {
        parent::configure();
        $this->setName('make:event')
            ->setDescription('Create a new event class');
    }

    protected function getStub()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'Stubs' . DIRECTORY_SEPARATOR . 'event.stub';
    }

    protected function getNamespace(string $app)
    {
        return parent::getNamespace($app) . '\\event';
    }
}