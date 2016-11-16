<?php

namespace Configurations;
/**
 * Class SSHTest
 */
class SSHTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Tradesy\Innobackupex\SSH\Configuration
     */
    private $ssh_configuration;
    /**
     * @var string
     */
    private $ssh_algorithm;
    /**
     * @var string
     */
    private $ssh_key;

    public function setUp()
    {
        $this->ssh_algorithm = "AES256";
        $this->ssh_key = "MY_STRING_ENCRYPTION_KEY";
        "127.0.0.1",
            22,
            "vagrant",
            "/home/vagrant/.ssh/id_rsa.pub",
            "/home/vagrant/.ssh/id_rsa",
            '',             // ssh key passphrase
            array('hostkey' => 'ssh-rsa')
    }

    /**
     *
     */
    public function tearDown()
    {
        $this->ssh_configuration = null;
    }


    private function setupSSHConfiguration()
    {
        $this->ssh_configuration = new \Tradesy\Innobackupex\SSH\Configuration(
            $this->ssh_algorithm,
            $this->ssh_key
        );
    }
    public function testSSHConfiguration()
    {

        $this->setupSSHConfiguration();
        $this->ssh_algorithm = "FAKE";
        $this->setExpectedException(\Tradesy\Innobackupex\Exceptions\SSHAlgorithmNotSupportedException::class);
        $this->setupSSHConfiguration();

        $this->ssh_algorithm = "AES192";
        $this->ssh_key = 1;
        $this->setupSSHConfiguration();

    }

    public function testSSHConfigurationInvalidKeyType()
    {
        $this->ssh_algorithm = "AES192";
        $this->ssh_key = 1;
        $this->setExpectedException(\Tradesy\Innobackupex\Exceptions\InvalidSSHKeyTypeException::class);
        $this->setupSSHConfiguration();
    }

    public
    function testSSHConfigurationInvalidKeyLength()
    {
        $this->ssh_algorithm = "AES192";
        $this->ssh_key = "";
        $this->setExpectedException(\Tradesy\Innobackupex\Exceptions\InvalidSSHKeyTypeException::class);
        $this->setupSSHConfiguration();
    }

}