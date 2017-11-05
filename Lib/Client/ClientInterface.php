<?php

namespace Brisum\Deploy\Lib\Client;

use Brisum\Deploy\Lib\FileManager\FileManagerInterface;

interface ClientInterface
{
    /**
     * @return array
     */
    public function getConfig();

    /**
     * @return FileManagerInterface
     */
    public function getFileManager();

    /**
     * @return bool
     */
    public function connect();

    /**
     * @return bool
     */
    public function close();

    /**
     * @return void
     */
    public function test();

    /**
     * @return bool
     */
    public function load();

    /**
     * @return bool
     */
    public function clear();

    /**
     * @param string $log
     * @return bool
     */
    public function getFilelist($log);

    /**
     * @param $path
     * @return bool
     */
    public function dbExport($path);

    /**
     * @return bool
     */
    public function dbImport($path);
}
