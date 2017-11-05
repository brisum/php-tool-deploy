<?php

namespace Brisum\Deploy\Lib\Client;

use Brisum\Deploy\Lib\FileManager\FileManagerInterface;
use Brisum\Lib\ObjectManager;
use Exception;

class FsClient implements ClientInterface
{
    /** @var  array */
    protected $config;

    /**
     * @var FileManagerInterface
     */
    protected $fileManager;

    /**
     * Ftp constructor.
     * @param array $config
     * @param ObjectManager $objectManager
     */
    public function __construct(array $config, ObjectManager $objectManager)
    {
        $this->config = $config;
        $this->fileManager = $objectManager->create('Brisum\Deploy\Lib\FileManager\FsFileManager');
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return FileManagerInterface
     */
    public function getFileManager()
    {
        return $this->fileManager;
    }

    /**
     * @return bool
     */
    public function connect()
    {
        return $this->fileManager->connect();
    }

    /**
     * @return bool
     */
    public function close()
    {
        return $this->fileManager->close();
    }

    /**
     * @return void
     */
    public function test()
    {
        foreach ($this->fileManager->dirList($this->config['base_path']) as $filename) {
            echo $filename . "\n";
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function load()
    {
        $this->fileManager->upload($this->config['base_path'] . 'exploit', DEPLOY_EXPLOIT_DIR);

        if (!file_exists(DEPLOY_TMP_DIR) && !mkdir(DEPLOY_TMP_DIR, 0755, true)) {
            throw new Exception('Can not create directory' . DEPLOY_TMP_DIR);
        }
        $ignoreFilePath = DEPLOY_TMP_DIR . 'ignore-files-' . date('Ymd-His') . '-' . mt_rand() . '.txt';
        file_put_contents($ignoreFilePath, implode("\n", $this->config['ignore_files']));
        $this->fileManager->upload($this->config['base_path'] . '/exploit/config/ignore_files', $ignoreFilePath);
        unlink($ignoreFilePath);

        $this->fileManager->chmod($this->config['base_path'] . 'exploit', 0755);
    }

    /**
     * @return bool
     */
    public function clear()
    {
        return $this->fileManager->rm($this->config['base_path'] . 'exploit');
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function getFilelist($log)
    {
        $dirLog = dirname($log);
        $url = sprintf('%s/exploit/file-list.php?path=%s', $this->config['site_url'], $this->config['abs_path']);
        $curl = curl_init();

        echo 'request ' . $url . "\n";
        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => $url,
                CURLOPT_TIMEOUT => 600
            ]
        );
        $response = curl_exec($curl);
        curl_close($curl);

        if (!file_exists($dirLog) && !mkdir($dirLog, 0755, true)) {
            throw new Exception('Can not create directory' . $dirLog);
        }

        $this->fileManager->download($log, $this->config['base_path'] . 'exploit/src/export/filelist');

        return $response;
    }

    /**
     * @param $path
     * @return bool
     * @throws Exception
     */
    public function dbExport($path)
    {
        $dirDump = dirname($path);
        $url = sprintf('%s/exploit/db/%s.php?action=export', $this->config['site_url'], $this->config['cms']);
        $curl = curl_init();

        echo 'request ' . $url . "\n";
        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => $url,
                CURLOPT_TIMEOUT => 600
            ]
        );
        $response = curl_exec($curl);
        curl_close($curl);;

        if (!file_exists($dirDump) && !mkdir($dirDump, 0755, true)) {
            throw new Exception('Can not create directory' . $dirDump);
        }

        $this->fileManager->download($path, $this->config['base_path'] . 'exploit/src/export/dump.sql');

        return $response;
    }

    /**
     * @return bool
     */
    public function dbImport()
    {
        /*
        self.ftpClient.upload(path, '/exploit/src/import/dump.sql')
        response = request.urlopen('%s/exploit/db/%s.php?action=import' % (self.config['site_url'], self.config['cms']))
        print(response.read())
        response.close()
         */
    }
}
