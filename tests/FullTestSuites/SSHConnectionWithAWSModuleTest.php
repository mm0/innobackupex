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
    public function testBucketNotExists(){
        $this->setExpectedException(\Tradesy\Innobackupex\Exceptions\BucketNotFoundException::class);
        $this->createConnection();

        $this->bucket = "fakebucket";
        new \Tradesy\Innobackupex\S3\Remote\Upload(
            $this->connection,
            $this->bucket,
            $this->region,
            $this->concurrency
        );
    }

    public function testBucketNotExists2(){
        $this->setExpectedException(\Tradesy\Innobackupex\Exceptions\BucketNotFoundException::class);
        $this->createConnection();

        $this->bucket = "fakebucket";
        new \Tradesy\Innobackupex\S3\Remote\Download(
            $this->connection,
            $this->bucket,
            $this->region,
            $this->concurrency
        );
    }

    public function testCliChecker(){
        $this->setExpectedException(\Tradesy\Innobackupex\Exceptions\CLINotFoundException::class);
        $this->createConnection();

        $a = new \Tradesy\Innobackupex\S3\Remote\Upload(
            $this->connection,
            $this->bucket,
            $this->region,
            $this->concurrency
        );;
        $reflection = new \ReflectionClass($a);
        $reflection_property = $reflection->getProperty('binary');
        $reflection_property->setAccessible(true);

        $reflection_property->setValue($a, "fakebinary_aws_cli");
        $a->testSave();

    }
    public function testCliChecker2(){
        $this->setExpectedException(\Tradesy\Innobackupex\Exceptions\CLINotFoundException::class);
        $this->createConnection();

        $a = new \Tradesy\Innobackupex\S3\Remote\Download(
            $this->connection,
            $this->bucket,
            $this->region,
            $this->concurrency
        );;
        $reflection = new \ReflectionClass($a);
        $reflection_property = $reflection->getProperty('binary');
        $reflection_property->setAccessible(true);

        $reflection_property->setValue($a, "fakebinary_aws_cli");
        $a->testSave();

    }
    /*
     * Called prior to restoration.
     * We want to remove any local backups prior to testing restoration with non-null Restore Modules
     */
    public function cleanupLocal()
    {
        parent::cleanupLocal();
        $info = $this->restore->getBackupInfo();

        foreach ($this->restore->getBackupArray() as $directory) {
            $backup_path = $info->getBaseBackupDirectory() . DIRECTORY_SEPARATOR . $directory;
            // directory should exist
            $this->assertTrue($this->connection->file_exists($backup_path));
            $this->connection->rmdir($backup_path);
            $this->assertFalse($this->connection->file_exists($backup_path));
        }
    }
}