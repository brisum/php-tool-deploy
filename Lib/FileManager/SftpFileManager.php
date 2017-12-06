<?php

namespace Brisum\Deploy\Lib\FileManager;

use Exception;
use FilesystemIterator;
use SplFileInfo;

class SftpFileManager implements FileManagerInterface
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
    protected $session;

    /**
     * @var resource
     */
    protected $sftpConnection;

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
        $this->session = ssh2_connect($this->host);

        if (!$this->session) {
            return false;
        }

        if(!ssh2_auth_password($this->session, $this->user, $this->password)) {
            return false;
        }

        $this->sftpConnection = ssh2_sftp($this->session);

        return true;
    }

    /**
     * @return boolean
     */
    public function close()
    {
        return true;
        // return $this->sftpConnection && ftp_close($this->sftpConnection);
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

            $isDownload &= file_put_contents(
                $localFile,
                file_get_contents('ssh2.sftp://' . $this->sftpConnection . $remoteFile)
            );

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

            $sftpStream = fopen('ssh2.sftp://' . $this->sftpConnection . $remoteFile, 'w');
            if (!$sftpStream) {
                throw new Exception('Could not open remote file: ' . $remoteFile);
            }
            try {
                $isUpload &= fwrite($sftpStream, file_get_contents($localFile));
                fclose($sftpStream);
            } catch (Exception $e) {
                // log error
                fclose($sftpStream);
                throw $e;
            }

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
        return (boolean)ssh2_sftp_mkdir($this->sftpConnection, $path, 0755, true);
    }

    /**
     * @param string $path
     * @return boolean
     */
    function rm($path)
    {
        if ($this->isFile($path)) {
            return ssh2_sftp_unlink($this->sftpConnection, $path);
        }
        if ($this->isDir($path)) {
            foreach ($this->dirList($path) as $item) {
                $this->rm("{$path}/$item");
            }
            return ssh2_sftp_rmdir($this->sftpConnection, $path);
        }
        return false;
    }

    /**
     * @param string $path
     * @return boolean
     */
    public function isExists($path)
    {
        return file_exists('ssh2.sftp://' . $this->sftpConnection . $path);
    }

    /**
     * @param string $path
     * @return boolean
     */
    public function isFile($path)
    {
        return is_file('ssh2.sftp://' . $this->sftpConnection . $path);
    }

    /**
     * @param string $path
     * @return boolean
     */
    public function isDir($path)
    {
        return is_dir('ssh2.sftp://' . $this->sftpConnection . $path);
    }

    /**
     * @param string $path
     * @return array
     */
    public function dirList($path)
    {
        $list = [];

        foreach (scandir('ssh2.sftp://' . $this->sftpConnection . $path) as $filepath) {
            if ('.' == $filepath || '..' == $filepath) {
                continue;
            }

            $list[] = $filepath;
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
        return ssh2_sftp_chmod($this->sftpConnection, $path, $permission);
    }
}
