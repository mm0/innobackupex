<?php

namespace Tradesy\Innobackupex\MySQL;

use \Tradesy\Innobackupex\Exceptions\MySQLConnectionException;

/**
 * A utility class, designed to store mysql credentials
 *
 *
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
        $port = 3306,
        $user = "root",
        $password = ""
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
            $this->port);
        if (!$connect || mysqli_connect_errno()) {
            throw new MySQLConnectionException(
                "Unable to Connect to MySQL Server Using Credentials provided. Error: " .
                mysqli_connect_errno(),
                0
            );
        }

    }
}
