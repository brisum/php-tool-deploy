<?php

namespace Brisum\Deploy\Console;

use Brisum\Deploy\Lib\Client\ClientFactory;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FilesDiffCommand extends Command
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
            ->setName('files:diff')

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
        $destination = $input->getArgument('configuration-destination');
        $configDestinationPath = DEPLOY_CONFIG_DIR . $destination . '.json';
        if (!file_exists($configDestinationPath)) {
            throw new Exception("Not found configuration " . $configDestinationPath);
        }
        $configDestination = json_decode(file_get_contents($configDestinationPath), true);
        $this->fetch($destination, $configDestination);
        $filesDestination = $this->readFiles(DEPLOY_TMP_DIR_FILES . $destination . '-files.txt');

        $source = $input->getArgument('configuration-source');
        $configSourcePath = DEPLOY_CONFIG_DIR . $source . '.json';
        if (!file_exists($configSourcePath)) {
            throw new Exception("Not found configuration " . $configSourcePath);
        }
        $configSource = json_decode(file_get_contents($configSourcePath), true);
        $configSource['ignore_files'] = $configDestination ['ignore_files'];
        $this->fetch($source, $configSource);
        $filesSource = $this->readFiles(DEPLOY_TMP_DIR_FILES . $source . '-files.txt');

        $diff = [
            'create' => [],
            'update' => [],
            'delete' => []
        ];

        foreach ($filesSource as $filepath => $filehash) {
            if (!isset($filesDestination[$filepath])) {
                $diff['create'][] = $filepath;
                continue;
            }
            if ($filehash != $filesDestination[$filepath]) {
                $diff['update'][] = $filepath;
            }
            unset($filesDestination[$filepath]);
        }
        foreach ($filesDestination as $filepath => $filehash) {
            $diff['delete'][] = $filepath;
        }

        if (!file_exists(DEPLOY_TMP_DIR_DIFF) && !mkdir(DEPLOY_TMP_DIR_DIFF)) {
            throw new Exception('Can not create directory ' . DEPLOY_TMP_DIR_DIFF);
        }
        foreach ($diff as $action => $files) {
            sort($files);
            file_put_contents(DEPLOY_TMP_DIR_DIFF . $action . '.txt' , implode("\n", $files));
        }
    }

    /**
     * @param string $name
     * @param array $config
     * @throws Exception
     */
    protected function fetch($name, array $config) {
        $client = $this->clientFactory->create($config);

        if(!$client->connect()) {
            throw new Exception('Can not connect to ' . $name);
        }
        $client->load();

        if (!file_exists(DEPLOY_TMP_DIR_FILES) && !mkdir(DEPLOY_TMP_DIR_FILES, 0777, true)) {
            throw new Exception('Can not create directory ' . DEPLOY_TMP_DIR_FILES);
        }

        echo $client->getFilelist(DEPLOY_TMP_DIR_FILES . $name .'-files.txt');

        $client->close();
    }

    /**
     * @param string $filepath
     * @return array
     */
    protected function readFiles($filepath) {
        $files = [];

        foreach (array_filter(explode("\n", file_get_contents($filepath))) as $fileInfo) {
            $fileInfo = explode('|', trim($fileInfo));
            $files[$fileInfo[0]] = $fileInfo[1];
        }

        return $files;
    }
}
