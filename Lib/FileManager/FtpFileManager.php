<?php

namespace Brisum\Deploy\Lib\FileManager;

use Exception;
use FilesystemIterator;
use SplFileInfo;

class FtpFileManager implements FileManagerInterface
{
    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $user;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var resource
     */
    protected $ftpConnection;

    /**
     * @param string $host
     * @param string $user
     * @param string $password
     */
    public function __construct($host, $user, $password)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * @return boolean
     */
    public function connect()
    {
        $this->ftpConnection = ftp_connect($this->host);

        if (!$this->ftpConnection) {
            return false;
        }

        if(!ftp_login($this->ftpConnection, $this->user, $this->password)) {
            ftp_close($this->ftpConnection);
            return false;
        }

        ftp_pasv($this->ftpConnection, true);

        return true;
    }

    /**
     * @return boolean
     */
    public function close()
    {
        return $this->ftpConnection && ftp_close($this->ftpConnection);
    }

    /**
     * @param string $localFile
     * @param string $remoteFile
     * @return boolean
     * @throws Exception
     */
    public function download($localFile, $remoteFile)
    {
        $isDownload = true;

        if ($this->isFile($remoteFile)) {
            $destDir = dirname($localFile);
            if (!file_exists($destDir) && !mkdir($destDir, 0755, true)) {
                throw new Exception('Can not create directory for file ' . $localFile);
            }

            $handle = fopen($localFile, 'w');
            $isDownload = ftp_fget($this->ftpConnection, $handle, $remoteFile, FTP_BINARY, 0);
            fclose($handle);

            return $isDownload;
        }

        if ($this->isDir($remoteFile)) {
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
        $isUpload = true;

        if (is_file($localFile)) {
            $remoteDir = dirname($remoteFile);
            if (!$this->isExists($remoteDir) && !$this->mkdir($remoteDir)) {
                throw new Exception('Can not create remote directory for file ' . $remoteFile);
            }

            $handle = fopen($localFile, 'r');
            $isUpload = ftp_fput($this->ftpConnection, $remoteFile, $handle, FTP_BINARY, 0);
            fclose($handle);

            return $isUpload;
        }

        if (is_dir($localFile)) {
            $fileIterator = new FilesystemIterator($localFile, FilesystemIterator::SKIP_DOTS);
            foreach ($fileIterator as $fileInfo) {
                /** @var SplFileInfo  $fileInfo */
                $isUpload = $isUpload && $this->upload(
                        rtrim($remoteFile, '/') . '/' . $fileInfo->getFilename(),
                        rtrim($localFile, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileInfo->getFilename()
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
        $parentDir = dirname($path);
        if (!$this->isExists($parentDir) && !$this->mkdir($parentDir)) {
            throw new Exception('Can not create remote directory ' . $parentDir);
        }

        return (boolean)ftp_mkdir($this->ftpConnection, $path);
    }

    /**
     * @param string $path
     * @return boolean
     */
    function rm($path)
    {
        if ($this->isFile($path)) {
            return ftp_delete($this->ftpConnection, $path);
        }
        if ($this->isDir($path)) {
            foreach ($this->dirList($path) as $item) {
                $this->rm("{$path}/$item");
            }
            return ftp_rmdir($this->ftpConnection, $path);
        }
        return false;
    }

    /**
     * @param string $path
     * @return boolean
     */
    public function isExists($path)
    {
        $list = ftp_nlist($this->ftpConnection, $path);
        return false !== $list && !empty($list);
    }

    /**
     * @param string $path
     * @return boolean
     */
    public function isFile($path)
    {
        return $this->isExists($path) && -1 !== ftp_size($this->ftpConnection, $path);
    }

    /**
     * @param string $path
     * @return boolean
     */
    public function isDir($path)
    {
        return $this->isExists($path) && -1 === ftp_size($this->ftpConnection, $path);
    }

    /**
     * @param string $path
     * @return array
     */
    public function dirList($path)
    {
        $list = [];

        foreach (ftp_nlist($this->ftpConnection, $path) as $filepath) {
            $filename = ltrim(str_replace($path, '', $filepath), '/');

            if ('.' == $filename || '..' == $filename) {
                continue;
            }

            $list[] = $filename;
        }

        return $list;
    }

    /**
     * @param string $path
     * @param string $permission
     * @return int
     */
    public function chmod($path, $permission)
    {
        return ftp_chmod($this->ftpConnection, $permission, $path);
    }
}
