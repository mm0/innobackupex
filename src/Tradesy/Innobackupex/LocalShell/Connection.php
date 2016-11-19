<?php

namespace Tradesy\Innobackupex\LocalShell;

use Tradesy\Innobackupex\ConnectionResponse;
use Tradesy\Innobackupex\Exceptions\ServerNotListeningException;
use Tradesy\Innobackupex\LoggingTraits;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * Class Connection
 * @package Tradesy\Innobackupex
 */
class Connection implements \Tradesy\Innobackupex\ConnectionInterface
{
    use LoggingTraits;

    /**
     * @var bool
     */
    protected $sudo_all = false;

    /**
     * @return boolean
     */
    public function isSudoAll()
    {
        return $this->sudo_all;
    }

    /**
     * @param boolean $sudo_all
     */
    public function setSudoAll($sudo_all)
    {
        $this->sudo_all = $sudo_all;
    }

    /**
     * Connection constructor.
     */
    function __construct()
    {
    }

    /**
     * @throws ServerNotListeningException
     */
    public function verify()
    {
    }

    /**
     * @return resource
     */
    public function getConnection($force_reconnect = false)
    {
        return $this;
    }

    /**
     * @return ConnectionResponse
     */
    public function executeCommand($command, $no_sudo = false)
    {
        $command = ($this->isSudoAll() && !$no_sudo ? "sudo " : "") . $command;

        // Hacky way to get stderr, but proc_open seems to block indefinitely
        $tmpfname = tempnam("/tmp", "innobackupex");
        $stdout = rtrim(shell_exec($command . " 2>$tmpfname"));
        $stderr = file_get_contents($tmpfname);
        unlink($tmpfname);

        /* Doesn't work for some reason
         $proc = proc_open(
            $command,[
            0 => ['pipe','r'],
            1 => ['pipe','w'],
            2 => ['pipe','w'],
        ],$pipes);
        stream_set_blocking($pipes[0], 0);
        stream_set_blocking($pipes[1], 1);
        stream_set_blocking($pipes[2], 1);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        proc_close($proc);
         */
        return new ConnectionResponse(
            $command,
            $stdout,
            $stderr
        );
    }

    /**
     * @param string $file
     * @return mixed
     */
    public function getFileContents($file)
    {
        if ($this->file_exists($file)) {
            $contents = file_get_contents($file);
        } else {
            $contents = "";
        }
        return $contents;
    }

    /**
     * @return string
     */
    public function getTemporaryDirectoryPath()
    {
        return "/tmp/";
    }

    /**
     * @param string $file
     * @param string $contents
     * @param int $mode
     * @return
     */
    public function writeFileContents($file, $contents, $mode = 0644)
    {
        return boolval(file_put_contents($file, $contents));
    }

    /**
     * @param string $file
     * @return boolean
     */
    public
    function file_exists($file)
    {
        return file_exists($file);
    }

    /**
     * @param string $directory
     * @return mixed
     */
    public function scandir($directory)
    {
        return scandir($directory);
    }

    /**
     * @param string $directory
     * @return mixed
     */
    public function mkdir($directory)
    {
        return mkdir($directory);
    }

    /**
     * @param string $directory
     * @return mixed
     */
    public function rmdir($directory)
    {
        $this->logWarning("Warning, *** method rmdir utilized *** on directory: " . $directory );
        if (is_dir($directory) === true) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory), RecursiveIteratorIterator::CHILD_FIRST);

            foreach ($files as $file) {
                if (in_array($file->getBasename(), array('.', '..')) !== true) {
                    if ($file->isDir() === true) {
                        rmdir($file->getPathName());
                    } else if (($file->isFile() === true) || ($file->isLink() === true)) {
                        unlink($file->getPathname());
                    }
                }
            }

            return rmdir($directory);
        } else if ((is_file($directory) === true) || (is_link($directory) === true)) {
            return unlink($directory);
        }

        return false;
    }
    public function recursivelyChownDirectory($directory, $owner, $group, $mode){
        $this->executeCommand("chown -R $owner:$group $directory");
        $this->executeCommand("chmod -R $mode $directory");
    }
}