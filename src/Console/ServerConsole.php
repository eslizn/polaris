<?php
namespace Polaris\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ServerConsole
 * @package Polaris\Console
 */
class ServerConsole extends Command
{

    /**
     * @var mixed
     */
    protected $server;

    /**
     * ServerCommand constructor.
     * @param mixed $server
     */
    public function __construct($server)
    {
        if (!is_subclass_of($server, ServerInterface::class)) {
            throw new \InvalidArgumentException('server must implements ServerInterface!', -__LINE__);
        }
        $this->server = $server;
        parent::__construct();
    }

    /**
     *
     */
    protected function configure()
    {
        $this->setName('server');
        $this->setDescription('Server start(default)|stop|restart|reload');
        $this->addArgument('cmd', InputArgument::OPTIONAL, 'command', 'start');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!in_array(strtolower($input->getArgument('cmd')), ['start', 'stop', 'restart', 'reload'])) {
            $output->writeln('<error>server only support start|stop|restart|reload command!</error>');
        }
        if (is_string($this->server)) {
            $this->server = new $this->server;
        }
        call_user_func([$this->server, $input->getArgument('cmd')]);
    }

}