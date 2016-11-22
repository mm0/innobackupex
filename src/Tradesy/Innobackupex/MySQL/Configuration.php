<?php

namespace Tradesy\Innobackupex\MySQL;

use \Tradesy\Innobackupex\Exceptions\MySQLConnectionException;
use Tradesy\Innobackupex\LoggingTraits;

/**
 * A utility class, designed to store Mysql credentials
 */
class Configuration
{
    use LoggingTraits;
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
    /**
     * @var string
     */
    protected $data_directory = "/var/lib/mysql";
    /**
     * @var string
     */
    protected $data_owner = "mysql";
    /**
     * @var string
     */
    protected $data_group = "mysql";


    /**
     * Configuration constructor.
     * @param string $host
     * @param string $user
     * @param string $password
     * @param int $port
     */
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
    }

    /**
     * @throws MySQLConnectionException
     */
    public function verify()
    {
        $connect = @mysqli_connect(
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
    /**
     * @return string
     */
    public function getDataDirectory()
    {
        return $this->data_directory;
    }
    /**
     * @param string $data_directory
     */
    public function setDataDirectory($data_directory)
    {
        $this->data_directory = $data_directory;
    }

    /**
     * @return string
     */
    public function getDataOwner()
    {
        return $this->data_owner;
    }

    /**
     * @param string $data_owner
     */
    public function setDataOwner($data_owner)
    {
        $this->data_owner = $data_owner;
    }

    /**
     * @return string
     */
    public function getDataGroup()
    {
        return $this->data_group;
    }

    /**
     * @param string $data_group
     */
    public function setDataGroup($data_group)
    {
        $this->data_group = $data_group;
    }

}
