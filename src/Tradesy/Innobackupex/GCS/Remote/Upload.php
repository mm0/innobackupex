<?php

namespace Tradesy\Innobackupex\GCS\Remote;

use \Tradesy\Innobackupex\SSH\Connection;
use \Tradesy\Innobackupex\SaveInterface;
use \Tradesy\Innobackupex\ConnectionInterface;
use \Tradesy\Innobackupex\Exceptions\CLINotFoundException;
use \Tradesy\Innobackupex\Exceptions\BucketNotFoundException;

class Upload implements SaveInterface {

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
     * @param ConnectionInterface $connection
     * @param string $bucket
     * @param string $key
     * @param string $region
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
        $this->concurrency              = $concurrency;
        $this->testSave();
    }
    public function testSave()
    {
        $command = "which " . $this->binary;
        $response = $this->connection->executeCommand($command);
        if(strlen($response->stdout()) == 0 
            || preg_match("/not found/i", $response->stdout())){
            throw new CLINotFoundException(
                $this->binary . " CLI not installed.",
                0
            );
        }
        /*
         * TODO: Check that credentials work
         */
        $command = $this->binary .
                    " ls | grep -c " . $this->bucket;
        echo $command;
        $response = $this->connection->executeCommand($command);
        if(intval($response->stdout())==0){
            throw new BucketNotFoundException(
                "GCS bucket (" . $this->bucket . ")  not found. ",
                0
            );
        }

    }

    public function save($filename)
    {
        // -m option for parallel
        $command = $this->binary .
            " -m rsync -r  $filename gs://" .
            $this->bucket . "/" . 
            $this->key;
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

    public function saveBackupInfo(\Tradesy\Innobackupex\Backup\Info $info, $filename){
        $serialized = serialize($info);

        $response = $this->connection->writeFileContents("/tmp/temporary_backup_info", $serialized);
        $command = $this->binary . " cp /tmp/temporary_backup_info gs://" . $this->bucket . "/tradesy_percona_backup_info";
        echo "Upload latest backup info to GCS with command: $command \n";

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