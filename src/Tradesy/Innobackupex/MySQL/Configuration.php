<?php

namespace Tradesy\Innobackupex\MySQL;

use \Tradesy\Innobackupex\Exceptions\MySQLConnectionException;

/**
 * A utility class, designed to store Mysql credentials
 */
class Configuration
{

    /**
     * @var string
     */
    protected $host;
    /**
     * @var int
     */
    protected $port;
    /**
     * @var string
     */
    protected $user;
    /**
     * @var string
     */
    protected $password;

    public function __construct(
        $host = "localhost",
        $user = "root",
        $password = "",
        $port = 3306

    ) {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
        $this->verify();
    }

    protected function verify()
    {
        $connect = mysqli_connect(
            $this->host,
            $this->user,
            $this->password,
            '',
            $this->port
        );
        if (!$connect || mysqli_connect_errno()) {
            throw new MySQLConnectionException(
                "Unable to Connect to MySQL Server Using Credentials provided. Error: " .
                mysqli_connect_errno(),
                0
            );
        }
    }
    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->user;
    }
    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }
    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }
    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }
}
