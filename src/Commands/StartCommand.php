<?php

namespace Reaction\PM\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class StartCommand
 * @package Reaction\PM\Commands
 */
class StartCommand extends Command
{
    use ConfigTrait;

    protected static $defaultName = 'start';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->configurePMOptions($this);
        $this->setDescription('Start application')
            ->addArgument('working-directory', InputArgument::OPTIONAL, 'Working directory', './');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->initializeConfig($input, $output);

    }
}