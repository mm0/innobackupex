<?php

/**
 * Created by PhpStorm.
 * User: matt
 * Date: 11/18/16
 * Time: 6:46 PM
 */
abstract class AbstractFullBackupThenRestoreTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var \Tradesy\Innobackupex\Backup\AbstractBackup
     */
    protected $backup;
    /**
     * @var \Tradesy\Innobackupex\Restore\Mysql
     */
    protected $restore;
    /**
     * @var \Tradesy\Innobackupex\MySQL\Configuration
     */
    protected $mysql_config;
    /**
     * @var \Tradesy\Innobackupex\ConnectionInterface
     */
    protected $connection;
    /**
     * @var array
     */
    protected $save_modules = array();
    /**
     * @var array
     */
    protected $restore_modules = array();
    /**
     * @var string
     */
    protected $mysql_host = "127.0.0.1";   /* this should be localhost (IP since not using unix socket) because we are connecting via ssh below */
    /**
     * @var string
     */
    protected $mysql_user = "root";
    /**
     * @var string
     */
    protected $mysql_password = "password";
    /**
     * @var
     */
    protected $mysql_port;
    /**
     * @var \Tradesy\Innobackupex\Encryption\Configuration
     */
    protected $encryption_configuration;
    /**
     * @var string
     */
    protected $encryption_algorithm;
    /**
     * @var string
     */
    protected $encryption_key;
    /**
     * @var string
     */
    protected $save_directory;
    /**
     * @var int
     */
    protected $parallel_threads;

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
    protected $hostkey = "ssh-rsa";

    protected $bucket = "innobackup-testing-bucket";
    protected $region = "us-west-1";
    protected $concurrency = 16;
    /**
     * @var \Tradesy\Innobackupex\SSH\Configuration
     */
    protected $ssh_configuration;
    /**
     *
     */
    public function setUp()
    {
        $this->mysql_host = "127.0.0.1";   /* this should be localhost (IP since not using unix socket) because we are connecting via ssh below */
        $this->mysql_user = "root";
        $this->mysql_password = "password";
        $this->mysql_port = 3306;
        $this->encryption_algorithm = "AES256";
        $this->encryption_key = "MY_STRING_ENCRYPTION_KEY";
        $this->save_directory = "/tmp/backup_unit/";
        $this->parallel_threads = 16;


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
        $this->save_modules = null;
        $this->restore_modules = null;
    }

    /**
     *
     */
    protected function createMySQLConfigurationObject()
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
    abstract protected function createConnection();

    abstract protected function setupSaveModules();

    abstract protected function setupRestoreModules();

    private function setupEncryptionConfiguration()
    {
        $this->encryption_configuration = new \Tradesy\Innobackupex\Encryption\Configuration(
            $this->encryption_algorithm,
            $this->encryption_key
        );
    }


    public
    function createFullBackupObject()
    {
        $this->createMySQLConfigurationObject();
        $this->createConnection();
        $this->setupSaveModules();
        $this->setupEncryptionConfiguration();

        if (!$this->connection->file_exists($this->save_directory)) {
            $this->connection->mkdir($this->save_directory);
        }
        $this->backup = new \Tradesy\Innobackupex\Backup\Full(
            $this->mysql_config,
            $this->connection,
            $this->save_modules,     // Array of save modules, minimum one
            $this->encryption_configuration,                  // Encryption configuration or null
            $compress = true,                           // Specify whether to compress backup
            $compress_threads = 16,                    // Specify # threads for compression
            $this->parallel_threads,                            // Specify # threads
            $encryption_threads = 16,                  // Specify # threads for encryption
            $memory = "4G",                             // Specify RAM Usage
            $this->save_directory,           // Specify the directory used to save backup
            $save_directory_prefix = "full_backup_"     // Specify prefix for the full backup name
        );

    }

    public function testCreateFullBackupObject()
    {
        $this->createFullBackupObject();
        $this->assertTrue(null == null);

    }

    /**
     * @depends testCreateFullBackupObject
     */
    public function testFullBackupBackup()
    {
        $this->createFullBackupObject();
        $this->backup->Backup();

    }

    public function createIncrementalBackupObject()
    {
        $this->createMySQLConfigurationObject();
        $this->createConnection();
        $this->setupSaveModules();
        $this->setupEncryptionConfiguration();
        $this->chownBackupDirectory();

        $this->backup = new \Tradesy\Innobackupex\Backup\Incremental(
            $this->mysql_config,
            $this->connection,
            $this->save_modules,     // Array of save modules, minimum one
            $this->encryption_configuration,                  // Encryption configuration or null
            $compress = true,                                   // Specify whether to compress backup
            $compress_threads = 16,                            // Specify # threads for compression
            $this->parallel_threads,                            // Specify # threads
            $encryption_threads = 16,                          // Specify # threads for encryption
            $memory = "4G",                                     // Specify RAM Usage
            $this->save_directory,                   // Specify the directory used to save backup
            $save_directory_prefix = "incremental_backup_"      // Specify prefix for to call the full backup
        );
    }

    public function testCreateIncrementalBackupObject()
    {
        $this->createIncrementalBackupObject();

    }

    /**
     * @depends testCreateIncrementalBackupObject
     */
    public function testIncrementalBackupBackup()
    {
        $this->createIncrementalBackupObject();
        $this->backup->fetchBackupInfo();
        $this->backup->Backup();

    }

    /**
     * @depends testCreateIncrementalBackupObject
     */
    public function testIncrementalBackupBackupAgain()
    {
        $this->createIncrementalBackupObject();
        $this->backup->fetchBackupInfo();
        $this->backup->Backup();


    }

    public function createRestoreBackupObject(){
        $this->createMySQLConfigurationObject();
        $this->createConnection();
        $this->setupEncryptionConfiguration();
        $this->setupRestoreModules();
        $BackupInfo = unserialize(
            $this->connection->getFileContents($this->save_directory ."tradesy_percona_backup_info")
        );

        $this->restore = new \Tradesy\Innobackupex\Restore\Mysql(
            $this->mysql_config,
            $this->connection,
            $this->restore_modules,     // Array of save modules, minimum one
            $this->encryption_configuration,                  // Encryption configuration or null
            $this->parallel_threads,                            // Specify # threads
            $encryption_threads = 16,                          // Specify # threads for encryption
            $memory = "4G"                                     // Specify RAM Usage
        );
        $this->restore->setBackupInfo($BackupInfo);
    }

    public function testRestoreBackup(){
        $this->createRestoreBackupObject();
        $this->mysql_config->setDataDirectory("/var/lib/mysql");
        $this->connection->executeCommand("service mysql stop");

        $this->chownDataDirectory();
//        $this->connection->rmdir($this->mysql_config->getDataDirectory());
        /* hack since otherwise we need to chown entire /var/lib */
        $this->connection->executeCommand("rm -rf /var/lib/mysql");

        $this->restore->runRestore();

        $this->chownDataDirectoryMysql();

        // TODO: test this without chowning
        $this->connection->executeCommand("service mysql bootstrap-pxc");

        $this->cleanupBackupDirectory();
    }

    protected function chownDataDirectory(){
        $this->mysql_config->setDataOwner("vagrant");
        $this->mysql_config->setDataGroup("vagrant");
        $this->chownDirectory($this->mysql_config->getDataDirectory());
    }
    protected function chownDataDirectoryMysql(){
        $this->mysql_config->setDataOwner("mysql");
        $this->mysql_config->setDataGroup("mysql");
        $this->chownDirectory($this->mysql_config->getDataDirectory());
    }
    protected function chownBackupDirectory(){
        $this->mysql_config->setDataOwner("vagrant");
        $this->mysql_config->setDataGroup("vagrant");
        $this->chownDirectory($this->save_directory);
    }
    protected function chownDirectory($directory){

        /*
         * chown directory
         */
        $command = "chown -R " . $this->mysql_config->getDataOwner() .
            "." .
            $this->mysql_config->getDataGroup() .
            " " .
            $directory;
        $this->connection->executeCommand($command);
        $command = "chmod 0777 -R " .
            $directory;
        $this->connection->executeCommand($command);
    }
    protected function cleanupBackupDirectory()
    {
        $this->chownBackupDirectory();
        $this->connection->rmdir($this->save_directory);
    }
}