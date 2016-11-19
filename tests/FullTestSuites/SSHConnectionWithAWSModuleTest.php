<?php

namespace FullTestSuites;
/**
 * Class SSHConnectionWithAWSModuleTest
 */
class SSHConnectionWithAWSModuleTest extends \AbstractFullBackupThenRestoreTest
{
    /**
     *
     */
    public function createConnection()
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
        $this->connection = new \Tradesy\Innobackupex\SSH\Connection(
            $this->ssh_configuration
        );
        $this->connection->setSudoAll(true);
    }

    public function tearDown(){
        $this->mysql_config = null;
        $this->connection = null;
        $this->ssh_configuration = null;
    }
    public function setupSaveModules()
    {
        $this->save_modules = array(
            new \Tradesy\Innobackupex\S3\Remote\Upload(
                $this->connection,
                $this->bucket,
                $this->region,
                $this->concurrency
            )
        );
    }

    public function setupRestoreModules()
    {
        $this->restore_modules = array(
            new \Tradesy\Innobackupex\S3\Remote\Download(
                $this->connection,
                $this->bucket,
                $this->region,
                $this->concurrency
            )
        );
    }

}