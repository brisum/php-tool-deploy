<?php

namespace Brisum\Deploy\Console;

use Brisum\Deploy\Lib\Client\ClientFactory;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FilesSyncCommand extends Command
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
            ->setName('files:sync')

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
        $sync = [
            'create' => array_filter(explode("\n", file_get_contents(DEPLOY_TMP_DIR_DIFF . 'create.txt'))),
            'update' => array_filter(explode("\n", file_get_contents(DEPLOY_TMP_DIR_DIFF . 'update.txt'))),
            'delete' => array_filter(explode("\n", file_get_contents(DEPLOY_TMP_DIR_DIFF . 'delete.txt'))),
        ];
        $sourceConfig = $this->getConfig($input->getArgument('configuration-source'));
        $sourceClient = $this->clientFactory->create($sourceConfig);
        $sourceFileManager = $sourceClient->getFileManager();
        $destinationConfig = $this->getConfig($input->getArgument('configuration-destination'));
        $destinationClient = $this->clientFactory->create($destinationConfig);
        $destinationFileManager = $destinationClient->getFileManager();

        if(!$sourceClient->connect()) {
            throw new Exception('Can not connect to ' . $sourceConfig);
        }
        if(!$destinationClient->connect()) {
            throw new Exception('Can not connect to ' . $destinationConfig);
        }

        if ('ftp' == $sourceConfig['type'] && 'fs' == $destinationConfig['type']) {
            foreach ($sync['create'] as $file) {
                printf("create %s\n", $file);
                $sourceFileManager->download($destinationConfig['base_path'] . $file, $sourceConfig['base_path'] . $file);
            }
            foreach ($sync['update'] as $file) {
                printf("update %s\n", $file);
                $sourceFileManager->download($destinationConfig['base_path'] . $file, $sourceConfig['base_path'] . $file);
            }
            foreach ($sync['delete'] as $file) {
                printf("delete %s\n", $file);
                $destinationFileManager->rm($destinationConfig['base_path'] . $file);
            }
        } elseif ('fs' == $sourceConfig['type'] && 'ftp' == $destinationConfig['type']) {
            foreach ($sync['create'] as $file) {
                printf("create %s\n", $file);
                $destinationFileManager->upload($destinationConfig['base_path'] . $file, $sourceConfig['base_path'] . $file);
            }
            foreach ($sync['update'] as $file) {
                printf("update %s\n", $file);
                $destinationFileManager->upload($destinationConfig['base_path'] . $file, $sourceConfig['base_path'] . $file);
            }
            foreach ($sync['delete'] as $file) {
                printf("delete %s\n", $file);
                $destinationFileManager->rm($destinationConfig['base_path'] . $file);
            }
        } else {
            throw new Exception('Not resolve sync ' . $sourceConfig['type'] . ' to ' . $destinationConfig['type']);
        }

        $sourceClient->clear();
        $sourceClient->close();
        $destinationClient->clear();
        $destinationClient->close();
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
