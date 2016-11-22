<?php

namespace Configurations;
/**
 * Class EncryptionTest
 */
class EncryptionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Tradesy\Innobackupex\Encryption\Configuration
     */
    private $encryption_configuration;
    /**
     * @var string
     */
    private $encryption_algorithm;
    /**
     * @var string
     */
    private $encryption_key;

    public function setUp()
    {
        $this->encryption_algorithm = "AES256";
        $this->encryption_key = "MY_STRING_ENCRYPTION_KEY";
    }

    /**
     *
     */
    public function tearDown()
    {
        $this->encryption_configuration = null;
    }


    private function setupEncryptionConfiguration()
    {
        $this->encryption_configuration = new \Tradesy\Innobackupex\Encryption\Configuration(
            $this->encryption_algorithm,
            $this->encryption_key
        );
    }
    public function testEncryptionConfiguration()
    {

        $this->setupEncryptionConfiguration();
        $this->encryption_algorithm = "FAKE";
        $this->setExpectedException(\Tradesy\Innobackupex\Exceptions\EncryptionAlgorithmNotSupportedException::class);
        $this->setupEncryptionConfiguration();

        $this->encryption_algorithm = "AES192";
        $this->encryption_key = 1;
        $this->setupEncryptionConfiguration();

    }

    public function testEncryptionConfigurationInvalidKeyType()
    {
        $this->encryption_algorithm = "AES192";
        $this->encryption_key = 1;
        $this->setExpectedException(\Tradesy\Innobackupex\Exceptions\InvalidEncryptionKeyTypeException::class);
        $this->setupEncryptionConfiguration();
    }

    public
    function testEncryptionConfigurationInvalidKeyLength()
    {
        $this->encryption_algorithm = "AES192";
        $this->encryption_key = "";
        $this->setExpectedException(\Tradesy\Innobackupex\Exceptions\InvalidEncryptionKeyTypeException::class);
        $this->setupEncryptionConfiguration();
    }

}