<?php

namespace Brisum\Deploy\Console;

use Brisum\Deploy\Lib\Client\ClientFactory;
use Brisum\Lib\ObjectManager;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command
{
    protected $clientFactory;

    public function __construct(ClientFactory $clientFactory)
    {
        parent::__construct();

        $this->clientFactory = $clientFactory;
    }

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('test')

            ->addArgument('configuration', InputArgument::REQUIRED, 'The name of server configuration')

            // the short description shown while running "php bin/console list"
            ->setDescription('Test connection.')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configuration = $input->getArgument('configuration');
        $configPath = DEPLOY_CONFIG_DIR . $configuration . '.json';

        if (!file_exists($configPath)) {
            throw new Exception("Not found configuration " . $configuration);
        }

        $config = json_decode(file_get_contents($configPath), true);
        $client = $this->clientFactory->create($config);

        if(!$client->connect()) {
            throw new Exception('Can not connect to ' . $configuration);
        }
        $client->test();
        $client->clear();
        $client->close();
    }
}
