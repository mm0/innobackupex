<?php

namespace Connections;

/**
 * Class SSHConnectionTest
 */
class SSHConnectionTest extends \AbstractConnectionTest
{
    /**
     * @var string
     */
    protected $host = "127.0.0.1";
    /**
     * @var int
     */
    protected $port = 22;
    /**
     * @var string
     */
    protected $user = "vagrant";
    /**
     * @var string
     */
    protected $passphrase = '';
    /**
     * @var string
     */
    protected $public_key_file = "/home/vagrant/.ssh/id_rsa.pub";
    /**
     * @var string
     */
    protected $private_key_file = "/home/vagrant/.ssh/id_rsa";
    /**
     * @var array
     */
    protected $ssh_options;
    private $hostkey = "ssh-rsa";
    /**
     * @var \Tradesy\Innobackupex\SSH\Configuration
     */
    private $ssh_configuration;
    /**
     *
     */
    public function setUp()
    {
        $this->ssh_configuration = new \Tradesy\Innobackupex\SSH\Configuration(
            $this->host,
            $this->port,
            $this->user,
            $this->public_key_file,
            $this->private_key_file,
            $this->passphrase,             // ssh key passphrase
            array('hostkey' => $this->hostkey)
        );
    }
    /**
     *
     */
    public function createConnection()
    {
        $this->connection = new \Tradesy\Innobackupex\SSH\Connection(
            $this->ssh_configuration
        );
        $this->connection->setSudoAll(true);
    }

    public function testSSHServerNotListeningException(){
        $this->port = 65001;
        $this->setExpectedException(\Tradesy\Innobackupex\Exceptions\ServerNotListeningException::class);
        $this->setUp();
        $this->createConnection();
    }

    public function testSSHCredentialsInvalid(){
        $this->user = "fakeuser";
        $this->setExpectedException(\Tradesy\Innobackupex\Exceptions\SSH2AuthenticationException::class);
        $this->setUp();
        $this->createConnection();
    }

    public function testGetConnectionException(){
        $this->setExpectedException(\Tradesy\Innobackupex\Exceptions\ServerNotListeningException::class);
        $this->port = 65001;

        $this->setUp();
        $a =  new \Tradesy\Innobackupex\SSH\Connection(
            $this->ssh_configuration
        );
        $reflection = new \ReflectionClass($a);
        $reflection_property = $reflection->getProperty('connection');
        $reflection_property->setAccessible(true);

        $reflection_property->setValue($a, false);
        $a->
        $this->setExpectedException(\Tradesy\Innobackupex\Exceptions\SSH2ConnectionException::class);
        $a->getConnection();
    }
}