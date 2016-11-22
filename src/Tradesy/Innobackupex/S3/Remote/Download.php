<?php

namespace Tradesy\Innobackupex\S3\Remote;

use \Tradesy\Innobackupex\LoadInterface;
use \Tradesy\Innobackupex\ConnectionInterface;
use \Tradesy\Innobackupex\Exceptions\CLINotFoundException;
use \Tradesy\Innobackupex\Exceptions\BucketNotFoundException;
use \Tradesy\Innobackupex\LoggingTraits;

class Download implements LoadInterface {

    use LoggingTraits;

    protected $connection;
    protected $bucket;
    protected $region;
    protected $source;
    protected $key;
    protected $remove_file_after_upload;
    protected $concurrency;
    protected $binary = "aws";

    /**
     * Upload constructor.
     * @param $connection
     * @param $bucket
     * @param $key
     * @param $region
     * @param int $concurrency
     * @param bool $remove_file_after_upload
     */
    public function __construct(
        ConnectionInterface $connection,
        $bucket,
        $region,
        $concurrency = 10,
        $remove_file_after_upload = false
    ){
        $this->connection               = $connection;
        $this->bucket                   = $bucket;
        $this->region                   = $region;
        $this->concurrency              = $concurrency;
        $this->remove_file_after_upload = $remove_file_after_upload;
        $this->testSave();
    }
    public function testSave()
    {
        $command = "which " . $this->binary;
        $response = $this->connection->executeCommand($command);
        if(strlen($response->stdout()) == 0 
            || preg_match("/not found/i", $response->stdout())){
            throw new CLINotFoundException(
                $this->binary ." CLI not installed.",
                0
            );
        }
        $command = $this->binary .
            " --region " . $this->region .
            " s3 ls " . $this->bucket ." 2>&1 | grep -c 'AllAccessDisabled\|NoSuchBucket'" ;
        $response = $this->connection->executeCommand($command);
        if(intval($response->stdout())==1){
            throw new BucketNotFoundException(
                "S3 bucket (" . $this->bucket . ")  not found in region (" . 
                $this->region .")",
                0
            );
        }

    }

    public function load( \Tradesy\Innobackupex\Backup\Info $info, $filename)
    {
        $local_filename = $info->getBaseBackupDirectory() . DIRECTORY_SEPARATOR . $filename;

        $this->key = DIRECTORY_SEPARATOR . $info->getRepositoryBaseName() . DIRECTORY_SEPARATOR . $filename;
        $command = $this->binary 
            ." s3 sync s3://" . $this->bucket . $this->key . " $local_filename";
        $this->logDebug($command);

        $response = $this->connection->executeCommand(
            $command
        );
        $this->logDebug($response->stdout());
        $this->logError($response->stderr());

    }
    public function cleanup()
    {
        /* $command = "sudo rm -f " . $this->getFullPathToBackup();
        return $this->connection->executeCommand(
            $command
        );
        */
    }

    public function getBackupInfo($backup_info_filename){

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