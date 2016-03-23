<?php

namespace Tradesy\Innobackupex\S3\Remote;

use \Tradesy\Innobackupex\SSH\Connection;
use \Tradesy\Innobackupex\SaveInterface;
use Tradesy\Innobackupex\ConnectionInterface;

class Upload implements SaveInterface {

    protected $connection;
    protected $bucket;
    protected $region;
    protected $source;
    protected $key;
    protected $remove_file_after_upload;
    protected $concurrency;

    /**
     * Upload constructor.
     * @param $connection
     * @param $bucket
     * @param $key
     * @param $region
     * @param bool $remove_file_after_upload
     * @param int $concurrency
     */
    public function __construct(
        ConnectionInterface $connection,
        $bucket,
        $key,
        $region,
        $remove_file_after_upload = false,
        $concurrency = 10
    ){
        $this->connection               = $connection;
        $this->bucket                   = $bucket;
        $this->key                      = $key;
        $this->region                   = $region;
        $this->remove_file_after_upload = $remove_file_after_upload;
        $this->concurrency              = $concurrency;
    }
    public function testSave()
    {
        // Check which aws tool exists to interface with Simple Storage Service
        // Check whether SSH tool exists

    }

    public function save($filename)
    {
        # upload compressed file to s3
        $command = "sudo s3cmd put $filename s3://" . $this->bucket . "/" . $this->key;
        return $this->connection->executeCommand(
            $command
        );

    }
    public function cleanup()
    {
        /* $command = "sudo rm -f " . $this->getFullPathToBackup();
        return $this->connection->executeCommand(
            $command
        );
        */
    }

    public function saveBackupInfo(\Tradesy\Innobackupex\Backup\Info $info){
        $serialized = serialize($info);

        $command = "echo '$serialized' | s3cmd put - s3://" . $this->bucket . "/tradesy_percona_backup_info";
        echo "Upload latest backup info to S3 with command: $command \n";
        echo $this->connection->executeCommand($command)->stdout();
        echo $this->connection->executeCommand($command)->stderr();

    }

    public function verify()
    {

    }

}