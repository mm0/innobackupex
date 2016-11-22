<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 11/18/16
 * Time: 6:58 PM
 */

namespace FullTestSuites;


class LocalConnectionWithAWSModuleTest extends \AbstractFullBackupThenRestoreTest
{
    /**
     *
     */
    public function createConnection()
    {
        $this->connection = new \Tradesy\Innobackupex\LocalShell\Connection();
        $this->connection->setSudoAll(true);
    }

    public function setupSaveModules()
    {
        $this->save_modules = array(
            new \Tradesy\Innobackupex\S3\Local\Upload(
                $this->connection,
                $this->bucket,
                $this->region,
                $this->concurrency
            )
        );
    }
    public function testBucketNotExistsSave(){
        $this->setExpectedException(\Tradesy\Innobackupex\Exceptions\BucketNotFoundException::class);
        $this->createConnection();
        new \Tradesy\Innobackupex\S3\Local\Download(
            $this->connection,
            $this->bucket."fakebucket",
            $this->region,
            $this->concurrency
        );
    }
    public function testBucketNotExists(){
        $this->setExpectedException(\Tradesy\Innobackupex\Exceptions\BucketNotFoundException::class);
        $this->createConnection();
        new \Tradesy\Innobackupex\S3\Local\Upload(
            $this->connection,
            $this->bucket."fakebucket",
            $this->region,
            $this->concurrency
        );
    }
    public function setupRestoreModules()
    {
        $this->createConnection();
        $this->restore_modules = array(
            new \Tradesy\Innobackupex\S3\Local\Download(
                $this->connection,
                $this->bucket,
                $this->region,
                $this->concurrency
            )
        );
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