<?php

namespace Brisum\Deploy\Lib\FileManager;

use Exception;
use FilesystemIterator;
use SplFileInfo;

class FsFileManager implements FileManagerInterface
{
    /**
     * @return boolean
     */
    public function connect()
    {
        return true;
    }

    /**
     * @return boolean
     */
    public function close()
    {
        return true;
    }

    /**
     * @param string $localFile
     * @param string $remoteFile
     * @return boolean
     * @throws Exception
     */
    public function download($localFile, $remoteFile)
    {
        if ($this->isFile($remoteFile)) {
            $destDir = dirname($localFile);
            if (!file_exists($destDir) && !mkdir($destDir, 0755, true)) {
                throw new Exception('Can not create directory for file ' . $localFile);
            }

            return copy($remoteFile, $localFile);
        }

        if ($this->isDir($remoteFile)) {
            $isDownload = true;
            foreach ($this->dirList($remoteFile) as $filename) {
                $isDownload = $isDownload && $this->download(
                        $remoteFile . '/' . $filename,
                        $localFile . DIRECTORY_SEPARATOR . $filename
                    );
            }
            return $isDownload;
        }

        throw new Exception('Remote file ' . $remoteFile . ' is not exists');
    }

    /**
     * @param string $remoteFile
     * @param string $localFile
     * @return boolean
     * @throws Exception
     */
    public function upload($remoteFile, $localFile)
    {
        if (is_file($localFile)) {
            $remoteDir = dirname($remoteFile);
            if (!$this->isExists($remoteDir) && !$this->mkdir($remoteDir)) {
                throw new Exception('Can not create remote directory for file ' . $remoteFile);
            }

            return copy($localFile, $remoteFile);
        }

        if (is_dir($localFile)) {
            $isUpload = true;
            $fileIterator = new FilesystemIterator($localFile, FilesystemIterator::SKIP_DOTS);
            foreach ($fileIterator as $fileInfo) {
                /** @var SplFileInfo  $fileInfo */
                $isUpload = $isUpload && $this->upload(
                        rtrim($remoteFile, '/') . '/' . $fileInfo->getFilename(),
                        $localFile . DIRECTORY_SEPARATOR . $fileInfo->getFilename()
                    );
            }
            return $isUpload;
        }

        throw new Exception('Local file' . $localFile . ' is not exists');
    }

    /**
     * @param string $path
     * @return boolean
     * @throws Exception
     */
    public function mkdir($path)
    {
        return mkdir($path, 0777, true);
    }

    /**
     * @param string $path
     * @return boolean
     */
    function rm($path)
    {
        if ($this->isFile($path)) {
            return unlink($path);
        } elseif ($this->isDir($path)) {
            return rmdir($path);
        }
        return true;
    }

    /**
     * @param string $path
     * @return boolean
     */
    public function isExists($path)
    {
        return file_exists($path);
    }

    /**
     * @param string $path
     * @return boolean
     */
    public function isFile($path)
    {
        return is_file($path);
    }

    /**
     * @param string $path
     * @return boolean
     */
    public function isDir($path)
    {
        return is_dir($path);
    }

    /**
     * @param string $path
     * @return array
     */
    public function dirList($path)
    {
        $list = [];

        $fileIterator = new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS);
        foreach ($fileIterator as $fileInfo) {
            /** @var SplFileInfo  $fileInfo */
            $list[] = $fileInfo->getFilename();
        }

        return $list;
    }

    /**
     * @param string $path
     * @param string $permission
     * @return bool
     */
    public function chmod($path, $permission)
    {
        return chmod($path, $permission);
    }
}
