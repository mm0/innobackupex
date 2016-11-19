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

    public function setupRestoreModules()
    {
        $this->restore_modules = array(
            new \Tradesy\Innobackupex\S3\Local\Download(
                $this->connection,
                $this->bucket,
                $this->region,
                $this->concurrency
            )
        );
    }
}