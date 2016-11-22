<?php

namespace Tradesy\Innobackupex\GCS\Remote;

use Tradesy\Innobackupex\Backup\Info;
use Tradesy\Innobackupex\LoggingTraits;
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
    )
    {
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
        $this->logInfo(($command));
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
        $this->logInfo($command);
        $response = $this->connection->executeCommand(
            $command
        );
        $this->logInfo($response->stdout());
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
            . " cp /tmp/temporary_backup_info gs://"
            . $this->bucket . "/tradesy_percona_backup_info";
        $this->logInfo("Upload latest backup info to S3 with command: $command");

        $response = $this->connection->executeCommand($command);
        $this->logInfo($response->stdout());
        $this->logError($response->stderr());

    }

    /**
     * @param Info $info
     * @param $filename
     */
    public function load(Info $info, $filename)
    {
        $local_filename = $info->getBaseBackupDirectory() . DIRECTORY_SEPARATOR . $filename;

        $this->key = DIRECTORY_SEPARATOR . $info->getRepositoryBaseName() . DIRECTORY_SEPARATOR . $filename;
        //  you must create destination directory prior to using gsutil rsync

        $this->logInfo("Creating Directory: " . $local_filename);
        $this->connection->mkdir($local_filename);

        $command = $this->binary
            . " -m rsync -r gs://" . $this->bucket . $this->key . " $local_filename";
        $this->logInfo($command);
        $response = $this->connection->executeCommand(
            $command
        );
        $this->logInfo($response->stdout());
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