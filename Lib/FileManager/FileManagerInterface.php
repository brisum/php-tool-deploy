<?php

namespace Brisum\Deploy\Lib\FileManager;

interface FileManagerInterface
{
    /**
     * @return boolean
     */
    public function connect();

    /**
     * @return boolean
     */
    public function close();

    /**
     * @param string $localFile
     * @param string $remoteFile
     * @return boolean
     */
    public function download($localFile, $remoteFile);

    /**
     * @param string $remoteFile
     * @param string $localFile
     * @return boolean
     */
    public function upload($remoteFile, $localFile);

    /**
     * @param string $path
     * @return boolean
     */
    public function mkdir($path);

    /**
     * @param string $path
     * @return boolean
     */
    public function rm($path);

    /**
     * @param string $path
     * @return boolean
     */
    function isExists($path);

    /**
     * @param string $path
     * @return boolean
     */
    public function isFile($path);

    /**
     * @param string $path
     * @return boolean
     */
    public function isDir($path);

    /**
     * @param string $path
     * @return array
     */
    public function dirList($path);

    /**
     * @param string $path
     * @param string $permission
     * @return boolean
     */
    public function chmod($path, $permission);
}
