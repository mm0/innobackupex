<?php

namespace Tradesy\Innobackupex\GCS\Remote;

use Tradesy\Innobackupex\Backup\Info;
use Tradesy\Innobackupex\LoggingTraits;
use \Tradesy\Innobackupex\SSH\Connection;
use \Tradesy\Innobackupex\ConnectionInterface;
use \Tradesy\Innobackupex\LoadInterface;
use \Tradesy\Innobackupex\Exceptions\CLINotFoundException;
use \Tradesy\Innobackupex\Exceptions\BucketNotFoundException;

/**
 * Class Download
 * @package Tradesy\Innobackupex\GCS\Remote
 */
class Download implements LoadInterface
{
    use LoggingTraits;
    /**
     * @var ConnectionInterface
     */
    protected $connection;
    /**
     * @var
     */
    protected $bucket;
    /**
     * @var
     */
    protected $region;
    /**
     * @var
     */
    protected $source;
    /**
     * @var
     */
    protected $key;
    /**
     * @var int
     */
    protected $concurrency;
    /**
     * @var string
     */
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
        $concurrency = 10
    ) {
        $this->connection = $connection;
        $this->bucket = $bucket;
        $this->region = $region;
        $this->concurrency = $concurrency;
        $this->testSave();
    }

    /**
     * @throws BucketNotFoundException
     * @throws CLINotFoundException
     */
    public function testSave()
    {
        $command = "which " . $this->binary;
        $response = $this->connection->executeCommand($command);
        if (strlen($response->stdout()) == 0 || preg_match("/not found/i", $response->stdout())) {
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
        $this->logTrace(($command));
        $response = $this->connection->executeCommand($command);
        if (intval($response->stdout()) == 0) {
            throw new BucketNotFoundException(
                "GCS bucket (" . $this->bucket . ")  not found. ",
                0
            );
        }

    }

    /**
     * @param $filename
     */
    public function save($filename)
    {
        # upload compressed file to s3
        $command = $this->binary . " s3 sync $filename s3://" . $this->bucket . "/" . $this->key;
        $this->logTrace($command);
        $response = $this->connection->executeCommand(
            $command
        );
        $this->logTrace($response->stdout());
        $this->logError($response->stderr());

    }

    /**
     *
     */
    public function cleanup()
    {
        /* $command = "sudo rm -f " . $this->getFullPathToBackup();
        return $this->connection->executeCommand(
            $command
        );
        */
    }

    /**
     * @param Info $info
     */
    public function saveBackupInfo(Info $info)
    {
        $serialized = serialize($info);

        $response = $this->connection->writeFileContents("/tmp/temporary_backup_info", $serialized);
        $command = $this->binary 
            . " s3 cp /tmp/temporary_backup_info s3://" 
            . $this->bucket . "/tradesy_percona_backup_info";
        $this->logTrace("Upload latest backup info to S3 with command: $command");

        $response = $this->connection->executeCommand($command);
        $this->logTrace($response->stdout());
        $this->logError($response->stderr());

    }

    /**
     * @param Info $info
     * @param $filename
     */
    public function load(Info $info, $filename)
    {
        $filename = $info->getLatestFullBackup();
        # upload compressed file to s3
        $command = $this->binary
            . " s3 sync $filename s3://" . $this->bucket . "/" . $this->key;
        $this->logTrace($command);
        $response = $this->connection->executeCommand(
            $command
        );
        $this->logTrace($response->stdout());
        $this->logError($response->stderr());

    }

    /**
     * @param $backup_info_filename
     */
    public function getBackupInfo($backup_info_filename)
    {

        /*$response = $this->connection->executeCommand($command);
        $this->logTrace($response->stdout());
        $this->logError($response->stderr());*/

    }

    /**
     *
     */
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