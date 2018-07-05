<?php

namespace Reaction\PM\Commands;

use Symfony\Component\Console\Command\Command;

/**
 * Class StartCommand
 * @package Reaction\PM\Commands
 */
class StartCommand extends Command
{
    /**
     * @inheritdoc
     */
    public function configure()
    {
        $this->setName('start')->setDescription('Start application');
    }
}