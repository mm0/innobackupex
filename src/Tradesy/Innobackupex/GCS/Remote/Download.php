<?php

namespace Tradesy\Innobackupex\GCS\Remote;

use \Tradesy\Innobackupex\SSH\Connection;
use \Tradesy\Innobackupex\ConnectionInterface;
use \Tradesy\Innobackupex\Exceptions\CLINotFoundException;
use \Tradesy\Innobackupex\Exceptions\BucketNotFoundException;

class Download implements LoadInterface {

    protected $connection;
    protected $bucket;
    protected $region;
    protected $source;
    protected $key;
    protected $remove_file_after_upload;
    protected $concurrency;
    protected $binary = "gsutil";

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
        $region,
        $remove_file_after_upload = false,
        $concurrency = 10
    ){
        $this->connection               = $connection;
        $this->bucket                   = $bucket;
        $this->region                   = $region;
        $this->remove_file_after_upload = $remove_file_after_upload;
        $this->concurrency              = $concurrency;
        $this->testSave();
    }
    public function testSave()
    {
        $command = "which " . $this->binary;
        $response = $this->connection->executeCommand($command);
        if(strlen($response->stdout()) == 0 || preg_match("/not found/i", $response->stdout())){
            throw new CLINotFoundException(
                "AWS CLI not installed.",
                0
            );
        }
        /*
         * TODO: Check that credentials work
         */
        $command = $this->binary .
                    " --region " . $this->region .
                    " s3 ls | grep -c " . $this->bucket;
        echo $command;
        $response = $this->connection->executeCommand($command);
        if(intval($response->stdout())==0){
            throw new BucketNotFoundException(
                "S3 bucket (" . $this->bucket . ")  not found in region (" . $this->region .")",
                0
            );
        }

    }

    public function save($filename)
    {
        # upload compressed file to s3
        $command = $this->binary ." s3 sync $filename s3://" . $this->bucket . "/" . $this->key;
        echo $command;
        $response = $this->connection->executeCommand(
            $command
        );
        echo $response->stdout();
        echo $response->stderr();

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

        $response = $this->connection->writeFileContents("/tmp/temporary_backup_info", $serialized);
        $command = $this->binary . " s3 cp /tmp/temporary_backup_info s3://" . $this->bucket . "/tradesy_percona_backup_info";
        echo "Upload latest backup info to S3 with command: $command \n";

        $response = $this->connection->executeCommand($command);
        echo $response->stdout();
        echo $response->stderr();

    }

    public function verify()
    {

    }
    /**
     * @param mixed $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }
}