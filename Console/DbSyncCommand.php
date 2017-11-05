<?php

namespace Brisum\Deploy\Console;

use Brisum\Deploy\Lib\Client\ClientFactory;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DbSyncCommand extends Command
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
            ->setName('db:sync')

            ->addArgument('configuration-source', InputArgument::REQUIRED, 'The name of server configuration')
            ->addArgument('configuration-destination',   InputArgument::REQUIRED, 'The name of server configuration')

            // the short description shown while running "php bin/console list"
            ->setDescription('Make files diff.')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $source = $input->getArgument('configuration-source');
        $sourceConfig = $this->getConfig($source);
        $sourceDump = DEPLOY_TMP_DIR_DB . $source . '-dump.sql';
        $destination = $input->getArgument('configuration-destination');
        $destinationConfig = $this->getConfig($destination);
        $destinationDump = DEPLOY_TMP_DIR_DB . $destination . '-dump.sql';
        $replacements = [
            $sourceConfig['site_url'] => $destinationConfig['site_url'],
            preg_replace('/https?:\/\//', '', $sourceConfig['site_url']) => preg_replace('/https?:\/\//', '', $destinationConfig['site_url'])
        ];

        $sourceCommand = $this->getApplication()->find('db:export');
        $sourceCommand->run(new ArrayInput(['command' => 'db:export', 'configuration' => $source,]), $output);

        if ($file = fopen($sourceDump, "r")) {
            file_put_contents($destinationDump, '');

            while(!feof($file)) {
                $line = fgets($file);

                foreach ($replacements as $search => $replace) {
                    $line = str_replace($search, $replace, $line);
                }

                file_put_contents($destinationDump, $line . "\n", FILE_APPEND);
            }
            fclose($file);
        }

        $sourceCommand = $this->getApplication()->find('db:import');
        $sourceCommand->run(new ArrayInput(['command' => 'db:import', 'configuration' => $destination,]), $output);
    }


    /**
     * @param string $configuration
     * @return array
     * @throws Exception
     */
    protected function getConfig($configuration) {
        $configPath = DEPLOY_CONFIG_DIR . $configuration . '.json';

        if (!file_exists($configPath)) {
            throw new Exception("Not found configuration " . $configuration);
        }

        return json_decode(file_get_contents($configPath), true);
    }
}
