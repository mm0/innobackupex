<?php

namespace Tradesy\Innobackupex\SSH;

use Tradesy\Innobackupex\Exceptions\SSH2AuthenticationException;
use Tradesy\Innobackupex\Exceptions\ServerNotListeningException;
use Tradesy\Innobackupex\Exceptions\SSH2ConnectionException;
use Tradesy\Innobackupex\ConnectionResponse;

/**
 * Class Connection
 * @package Tradesy\Innobackupex
 */
class Connection implements \Tradesy\Innobackupex\ConnectionInterface
{
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

    function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    public function verify()
    {
        $this->verifySSHServerListening();
        $this->verifyConnection();
    }

    function getConnection($force_reconnect = false)
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

    public function executeCommand($command)
    {
        $stream = ssh2_exec(
            $this->getConnection,
            $command
        );
        $stderrStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
        stream_set_blocking($stream, true);
        stream_set_blocking($stderrStream, true);
        $stdout = stream_get_contents($stream);
        $stderr = stream_get_contents($stderrStream);

        return new ConnectionResponse(
            $command,
            $stdout,
            $stderr
        );
    }

    public function getFileContents($file)
    {
        $temp_file = tempnam();
        ssh2_scp_recv($this->getConnection(), $file, $temp_file);
        $contents = file_get_contents($temp_file);
        delete($temp_file);
        return $contents;
    }

    public function writeFileContents($file, $contents, $mode=0644)
    {
        $temp_file = tempnam();
        file_put_contents($temp_file,$contents);
        ssh2_scp_send($this->getConnection(), $temp_file, $file, $mode);
        delete($temp_file);
    }

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

    protected
    function verifyConnection()
    {
        if ($this->authenticated) {
            return;
        } else {
            return $this->verifyCredentials();
        }

    }
}