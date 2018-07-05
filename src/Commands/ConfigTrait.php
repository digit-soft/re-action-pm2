<?php

namespace Reaction\PM\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;

trait ConfigTrait
{
    protected $file = './pm.json';

    /**
     * Configure PM options.
     * Usually called on command ::configure()
     * @param Command $command
     */
    protected function configurePMOptions(Command $command)
    {
        $command
            ->addOption('bridge', null, InputOption::VALUE_REQUIRED, 'Bridge for converting React Psr7 requests to target framework.', 'HttpKernel')
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Load-Balancer host. Default is 127.0.0.1', '127.0.0.1')
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'Load-Balancer port. Default is 8080', 8080)
            ->addOption('workers', null, InputOption::VALUE_REQUIRED, 'Worker count. Default is 8. Should be minimum equal to the number of CPU cores.', 8)
            ->addOption('app-env', null, InputOption::VALUE_REQUIRED, 'The environment that your application will use to bootstrap (if any)', 'dev')
            ->addOption('debug', null, InputOption::VALUE_REQUIRED, 'Enable/Disable debugging so that your application is more verbose, enables also hot-code reloading. 1|0', 0)
            ->addOption('logging', null, InputOption::VALUE_REQUIRED, 'Enable/Disable http logging to stdout. 1|0', 1)
            ->addOption('static-directory', null, InputOption::VALUE_REQUIRED, 'Static files root directory, if not provided static files will not be served', '')
            ->addOption('max-requests', null, InputOption::VALUE_REQUIRED, 'Max requests per worker until it will be restarted', 1000)
            ->addOption('ttl', null, InputOption::VALUE_REQUIRED, 'Time to live for a worker until it will be restarted', null)
            ->addOption('populate-server-var', null, InputOption::VALUE_REQUIRED, 'If a worker application uses $_SERVER var it needs to be populated by request data 1|0', 1)
            ->addOption('bootstrap', null, InputOption::VALUE_REQUIRED, 'Class responsible for bootstrapping the application', 'PHPPM\Bootstraps\Symfony')
            ->addOption('cli-path', null, InputOption::VALUE_REQUIRED, 'Full path to the php-cli executable', false)
            ->addOption('socket-path', null, InputOption::VALUE_REQUIRED, 'Path to a folder where socket files will be placed. Relative to working-directory or cwd()', '.pm/run/')
            ->addOption('pidfile', null, InputOption::VALUE_REQUIRED, 'Path to a file where the pid of the master process is going to be stored', '.pm/pm.pid')
            ->addOption('reload-timeout', null, InputOption::VALUE_REQUIRED, 'The number of seconds to wait before force closing a worker during a reload, or -1 to disable. Default: 30', 30)
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to config file', '');
    }

    /**
     * Render PM config as table in console
     * @param OutputInterface $output
     * @param array           $config
     */
    protected function renderConfig(OutputInterface $output, array $config)
    {
        $table = new Table($output);

        $rows = array_map(function ($a, $b) {
            return [$a, $b];
        }, array_keys($config), $config);
        $table->addRows($rows);

        $table->render();
    }

    /**
     * Get path where config file located
     * @param InputInterface $input
     * @param bool           $create
     * @return string
     * @throws \Exception
     */
    protected function getConfigPath(InputInterface $input, $create = false)
    {
        $configOption = $input->getOption('config');
        if ($configOption && !file_exists($configOption)) {
            if ($create) {
                file_put_contents($configOption, json_encode([]));
            } else {
                throw new \Exception(sprintf('Config file not found: "%s"', $configOption));
            }
        }
        $possiblePaths = [
            $configOption,
            $this->file,
            sprintf('%s/%s', dirname($GLOBALS['argv'][0]), $this->file)
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return realpath($path);
            }
        }
        return '';
    }

    /**
     * Load PM config
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return array|mixed
     * @throws \Exception
     */
    protected function loadConfig(InputInterface $input, OutputInterface $output)
    {
        $config = [];

        if ($path = $this->getConfigPath($input)) {
            $content = file_get_contents($path);
            $config = json_decode($content, true);
        }

        $config['bridge'] = $this->optionOrConfigValue($input, 'bridge', $config);
        $config['host'] = $this->optionOrConfigValue($input, 'host', $config);
        $config['port'] = (int)$this->optionOrConfigValue($input, 'port', $config);
        $config['workers'] = (int)$this->optionOrConfigValue($input, 'workers', $config);
        $config['app-env'] = $this->optionOrConfigValue($input, 'app-env', $config);
        $config['debug'] = $this->optionOrConfigValue($input, 'debug', $config);
        $config['logging'] = $this->optionOrConfigValue($input, 'logging', $config);
        $config['static-directory'] = $this->optionOrConfigValue($input, 'static-directory', $config);
        $config['bootstrap'] = $this->optionOrConfigValue($input, 'bootstrap', $config);
        $config['max-requests'] = (int)$this->optionOrConfigValue($input, 'max-requests', $config);
        $config['ttl'] = (int)$this->optionOrConfigValue($input, 'ttl', $config);
        $config['populate-server-var'] = (boolean)$this->optionOrConfigValue($input, 'populate-server-var', $config);
        $config['socket-path'] = $this->optionOrConfigValue($input, 'socket-path', $config);
        $config['pidfile'] = $this->optionOrConfigValue($input, 'pidfile', $config);
        $config['reload-timeout'] = $this->optionOrConfigValue($input, 'reload-timeout', $config);

        $config['cli-path'] = $this->optionOrConfigValue($input, 'cli-path', $config);

        if (false === $config['cli-path']) {
            //not set in config nor in command options -> autodetect path
            $executableFinder = new PhpExecutableFinder();
            $config['cli-path'] = $executableFinder->find();

            if (false === $config['cli-path']) {
                $output->writeln('<error>PM could find a php-cgi path. Please specify by --cli-path=</error>');
                exit(1);
            }
        }

        return $config;
    }

    /**
     * Get option from input or from config
     * @param InputInterface       $input
     * @param string               $name
     * @param                array $config
     * @return mixed
     */
    protected function optionOrConfigValue(InputInterface $input, $name, $config)
    {
        if ($input->hasParameterOption('--' . $name)) {
            return $input->getOption($name);
        }

        return isset($config[$name]) ? $config[$name] : $input->getOption($name);
    }

    /**
     * Initialize config (locate and load)
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param bool $render
     * @return array|mixed
     */
    protected function initializeConfig(InputInterface $input, OutputInterface $output, $render = true)
    {
        if ($workingDir = $input->getArgument('working-directory')) {
            chdir($workingDir);
        }
        $config = $this->loadConfig($input, $output);

        if ($path = $this->getConfigPath($input)) {
            $modified = '';
            $fileConfig = json_decode(file_get_contents($path), true);
            if (json_encode($fileConfig) !== json_encode($config)) {
                $modified = ', modified by command arguments';
            }
            $output->writeln(sprintf('<info>Read configuration %s%s.</info>', $path, $modified));
        }
        $output->writeln(sprintf('<info>%s</info>', getcwd()));

        if ($render) {
            $this->renderConfig($output, $config);
        }
        return $config;
    }
}
