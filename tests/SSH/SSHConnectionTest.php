<?php

/**
 * Class SSHConnectionTest
 */
class SSHConnectionTest extends PHPUnit_Framework_TestCase
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
    protected $passphrase;
    /**
     * @var string
     */
    protected $public_key_file;
    /**
     * @var string
     */
    protected $private_key_file;
    /**
     * @var array
     */
    protected $ssh_options;

    /**
     * @var \Tradesy\Innobackupex\SSH\Configuration
     */
    private $ssh_configuration;

    /**
     * @var \Tradesy\Innobackupex\ConnectionInterface
     */
    private $connection;
    /**
     *
     */
    public function setUp()
    {
        $this->ssh_configuration = new \Tradesy\Innobackupex\SSH\Configuration (
            "127.0.0.1",
            22,
            "vagrant",
            "/home/vagrant/.ssh/id_rsa.pub",
            "/home/vagrant/.ssh/id_rsa",
            '',             // ssh key passphrase
            array('hostkey' => 'ssh-rsa')
        );
    }

    /**
     *
     */
    public function tearDown()
    {
        $this->connection = null;
    }

    /**
     *
     */
    private function createConnection()
    {
        $this->connection = new \Tradesy\Innobackupex\SSH\Connection(
            $this->ssh_configuration
        );
        $this->connection->setSudoAll(true);
    }

    public function testLocalShellConnection()
    {
        $this->createConnection();
        $this->connection->verify();

        $this->connection->setSudoAll(false);
        $this->assertInstanceOf(\Tradesy\Innobackupex\ConnectionInterface::class, $this->connection);
        $command = "whoami";
        $this->assertFalse($this->connection->isSudoAll());
        $response = $this->connection->executeCommand($command);

        $this->assertInstanceOf(\Tradesy\Innobackupex\ConnectionResponse::class, $response);
        $this->assertEquals($command, $response->command());

        // Assuming testing in vagrant rather than elsewhere
        $this->assertEquals("vagrant", $response->stdout());

        $this->connection->setSudoAll(true);
        $this->assertTrue($this->connection->isSudoAll());

        $response = $this->connection->executeCommand($command);
        $this->assertEquals("sudo " . $command, $response->command());
        $this->assertEquals("root", $response->stdout());

        $this->connection->setSudoAll(false);
        $random = substr(md5(rand()), 0, 7);
        $contents = "unit_test" . $random;
        $tmp_file = $this->connection->getTemporaryDirectoryPath() . $contents;
        $result = $this->connection->writeFileContents($tmp_file, $contents);
        $this->assertTrue($result);

        $this->assertTrue($this->connection->file_exists($tmp_file));

        $file_contents = $this->connection->getFileContents($tmp_file);
        $this->assertEquals($contents, $file_contents);

        $scan = $this->connection->scandir($this->connection->getTemporaryDirectoryPath());
        $this->assertNotFalse($scan);

        $this->assertTrue($this->connection->mkdir($tmp_file . "dir"));

        /* TODO: Test for cli installed check */
    }
}