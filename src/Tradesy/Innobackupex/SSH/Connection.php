<?php

namespace Tradesy\Innobackupex\SSH;

use Tradesy\Innobackupex\ConnectionInterface;
use Tradesy\Innobackupex\Exceptions\SSH2AuthenticationException;
use Tradesy\Innobackupex\Exceptions\ServerNotListeningException;
use Tradesy\Innobackupex\Exceptions\SSH2ConnectionException;
use Tradesy\Innobackupex\ConnectionResponse;
use Tradesy\Innobackupex\LoggingTraits;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * Class Connection
 * @package Tradesy\Innobackupex
 */
class Connection implements ConnectionInterface
{
    use LoggingTraits;
    
    /**
     * @var Configuration
     */
    protected $config;
    /**
     * @var bool
     */
    protected $authenticated = false;
    /**
     * @var resource
     */
    protected $connection;

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

    function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->verify();
    }

    /**
     * @throws ServerNotListeningException
     */
    public function verify()
    {
        $this->verifySSHServerListening();
        $this->verifyConnection();
    }

    /**
     * @return resource
     */
    public function getConnection($force_reconnect = false)
    {
        if ($this->authenticated && !$force_reconnect) {
            return $this->connection;
        }
        $this->connection = ssh2_connect(
            $this->config->host(),
            $this->config->port(),
            $this->config->options()
        );
        if (!$this->connection) {
            throw new SSH2ConnectionException(
                "Connection to SSH Server failed unreachable at host: " . $this->config->port() .
                ":" . $this->config->port(),
                0
            );
        }

        return $this->connection;
    }

    /**
     * @return ConnectionResponse
     */
    public function executeCommand($command, $no_sudo = false)
    {
        $command = ($this->isSudoAll() && !$no_sudo ? "sudo " : "") . $command;
        $stream = ssh2_exec(
            $this->getConnection(),
            $command,
            true
        );
        $stderrStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
        stream_set_blocking($stream, true);
        stream_set_blocking($stderrStream, true);
        $stdout = rtrim(stream_get_contents($stream));
        $stderr = rtrim(stream_get_contents($stderrStream));

        return new ConnectionResponse(
            $command,
            $stdout,
            $stderr
        );
    }

    /**
     * @param string $file
     * @return mixed
     * @throws SSH2ConnectionException
     */
    public function getFileContents($file)
    {
        $temp_file = tempnam($this->getTemporaryDirectoryPath(), "");
        $this->logDebug("Temp filename generated: " . $temp_file);
        if (ssh2_scp_recv($this->getConnection(), $file, $temp_file)) {
            $contents = file_get_contents($temp_file);
        } else {
            $contents = "";
        }
        unlink($temp_file);

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
     * @throws SSH2ConnectionException
     */
    public function writeFileContents($file, $contents, $mode = 0644)
    {
        $this->logInfo("Writing file: " . $file);
        $temp_file = tempnam($this->getTemporaryDirectoryPath(), "");
        file_put_contents($temp_file, $contents);
        $result = ssh2_scp_send($this->getConnection(), $temp_file, $file, $mode);
        unlink($temp_file);
        return boolval($result);
    }

    /**
     * @throws SSH2AuthenticationException
     * @throws SSH2ConnectionException
     */
    protected
    function verifyCredentials()
    {

        $resource = ssh2_auth_pubkey_file(
            $this->getConnection(),
            $this->config->user(),
            $this->config->publicKey(),
            $this->config->privateKey(),
            $this->config->passphrase()
        );
        if (!$resource) {
            throw new SSH2AuthenticationException(
                "Authentication  to SSH Server failed. Check credentials: ",
                0
            );
        } else {
            $this->authenticated = true;
        }
    }

    /**
     * @return bool
     * @throws ServerNotListeningException
     */
    protected
    function verifySSHServerListening()
    {
        $serverConn = @stream_socket_client(
            "tcp://" . $this->config->host() . ":" . $this->config->port(),
            $errno,
            $errstr);

        if ($errstr != '') {
            throw new ServerNotListeningException(
                "SSH Server is unreachable at host: " . $this->config->port() .
                ":" . $this->config->port(),
                0
            );
        }
        fclose($serverConn);

        return true;
    }

    /**
     * @throws SSH2AuthenticationException
     */
    protected function verifyConnection()
    {
        if ($this->authenticated) {
            return;
        } else {
            return $this->verifyCredentials();
        }
    }

    /**
     * @param string $file
     * @return boolean
     */
    public function file_exists($file)
    {
        // Note: This might cause segfault if file doesn't exist due to ssh2 lib bug
        $sftp = ssh2_sftp($this->getConnection());

        return file_exists('ssh2.sftp://' . $sftp . $file);
    }

    /**
     * @param string $directory
     * @return mixed
     */
    public function scandir($directory)
    {
        $sftp = ssh2_sftp($this->getConnection());

        return scandir('ssh2.sftp://' . $sftp . $directory);
    }

    /**
     * @param string $directory
     * @return mixed
     */
    public function mkdir($directory)
    {
        $sftp = ssh2_sftp($this->getConnection());
        return mkdir('ssh2.sftp://' . $sftp . $directory);
    }

    /**
     * @param string $directory
     * @return mixed
     */
    public function rmdir($directory)
    {
        $sftp = ssh2_sftp($this->getConnection());
        return ssh2_sftp_rmdir($sftp,$directory);
    }
    public function recursivelyChownDirectory($directory, $owner, $group, $mode){
        $this->executeCommand("chown -R $owner:$group $directory");
        $this->executeCommand("chmod -R $mode $directory");
    }
}