<?php

namespace Reaction\PM\Commands;

use Symfony\Component\Console\Command\Command;

/**
 * Class StartCommand
 * @package Reaction\PM\Commands
 */
class StartCommand extends Command
{
    protected static $defaultName = 'start';

    public function __construct(?string $name = null)
    {
        parent::__construct($name);
        $this->setDescription('Start application');
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
    }
}