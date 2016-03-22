<?php

namespace Tradesy\Innobackupex\SSH;

use Tradesy\Innobackupex\Exceptions\SSH2AuthenticationException;
use Tradesy\Innobackupex\Exceptions\ServerNotListeningException;
use Tradesy\Innobackupex\Exceptions\SSH2ConnectionException;

/**
 * Class Connection
 * @package Tradesy\Innobackupex
 */
class Connection implements ConnectionInterface
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

    protected function verify(){
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

    protected function verifyCredentials()
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

    protected function verifySSHServerListening()
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

    protected function verifyConnection()
    {
        if ($this->authenticated) {
            return;
        } else {
            return $this->verifyCredentials();
        }

    }
}