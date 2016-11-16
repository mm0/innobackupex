<?php

namespace Configurations;
/**
 * Class MysqlTest
 */
class MysqlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Tradesy\Innobackupex\MySQL\Configuration
     */
    private $mysql_config;
    /**
     * @var string
     */
    private $mysql_host = "127.0.0.1";   /* this should be localhost (IP since not using unix socket) because we are connecting via ssh below */
    /**
     * @var string
     */
    private $mysql_user = "root";
    /**
     * @var string
     */
    private $mysql_password = "password";
    /**
     * @var
     */
    private $mysql_port;

    public function setUp()
    {
        $this->mysql_host = "127.0.0.1";   /* this should be localhost (IP since not using unix socket) because we are connecting via ssh below */
        $this->mysql_user = "root";
        $this->mysql_password = "password";
        $this->mysql_port = 3306;
        $this->encryption_algorithm = "AES256";
        $this->encryption_key = "MY_STRING_ENCRYPTION_KEY";
        $this->save_directory = "/tmp/backup_unit/";
        $this->parallel_threads = 100;

        // TODO: might need to delete database and run mysql_install_db after restore
        // also: service mysql bootstrap-pxc
        // also: generate random data in database
    }

    /**
     *
     */
    public function tearDown()
    {
        $this->mysql_config = null;
        $this->connection = null;
    }

    /**
     *
     */
    private function createMySQLConfigurationObject()
    {
        // Create MySQL configuration object
        $this->mysql_config = new \Tradesy\Innobackupex\MySQL\Configuration (
            $this->mysql_host,
            $this->mysql_user,
            $this->mysql_password,
            $this->mysql_port
        );
    }

    /**
     *
     */
    public function testCreateMysqlConfigurationObject()
    {
        // Setup
        $this->createMySQLConfigurationObject();
        $this->mysql_config->verify();
        // Defaults
        $default_data_directory = "/var/lib/mysql";
        $default_data_owner = $default_data_group = "mysql";

        $this->assertEquals($this->mysql_host, $this->mysql_config->getHost());
        $this->assertEquals($this->mysql_user, $this->mysql_config->getUsername());
        $this->assertEquals($this->mysql_password, $this->mysql_config->getPassword());
        $this->assertEquals($this->mysql_port, $this->mysql_config->getPort());
        $this->assertEquals($default_data_directory, $this->mysql_config->getDataDirectory(), "Check default Data Directory");
        $this->mysql_config->setDataDirectory($default_data_directory . "2");
        $this->assertEquals($default_data_directory . "2", $this->mysql_config->getDataDirectory(), "Check Data Directory get/set");


        $this->assertEquals($default_data_owner, $this->mysql_config->getDataOwner(), "Check default Data Owner");
        $this->assertEquals($default_data_group, $this->mysql_config->getDataGroup(), "Check default Data Group");
        $this->mysql_config->setDataOwner($default_data_owner . "2");
        $this->mysql_config->setDataGroup($default_data_group . "2");
        $this->assertEquals($default_data_group . "2", $this->mysql_config->getDataGroup());
        $this->assertEquals($default_data_owner . "2", $this->mysql_config->getDataOwner());

    }

}