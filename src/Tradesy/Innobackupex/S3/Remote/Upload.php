<?php

namespace Tradesy\Innobackupex\S3\Remote;

use Tradesy\Innobackupex\LoggingTraits;
use \Tradesy\Innobackupex\SaveInterface;
use \Tradesy\Innobackupex\ConnectionInterface;
use \Tradesy\Innobackupex\Exceptions\CLINotFoundException;
use \Tradesy\Innobackupex\Exceptions\BucketNotFoundException;

/**
 * Class Upload
 * @package Tradesy\Innobackupex\S3\Remote
 */
class Upload implements SaveInterface
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
     * @var
     */
    protected $remove_file_after_upload;
    /**
     * @var int
     */
    protected $concurrency;
    /**
     * @var string
     */
    protected $binary = "aws";

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
        $this->verify();
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
            " --region " . $this->region .
            " s3 ls " . $this->bucket ." 2>&1 | grep -c 'AllAccessDisabled\|NoSuchBucket'" ;
        $response = $this->connection->executeCommand($command);
        if (intval($response->stdout()) == 1) {
            throw new BucketNotFoundException(
                "S3 bucket (" . $this->bucket . ")  not found in region (" . $this->region . ")",
                0
            );
        }

    }

    /**
     * @param string $filename
     */
    public function save($filename)
    {
        # upload compressed file to s3
        $command = $this->binary .
            " s3 sync $filename s3://" .
            $this->bucket .
            "/" .
            $this->key;
        $this->logDebug($command);
        $response = $this->connection->executeCommand(
            $command
        );
        $this->logDebug($response->stdout());
        $this->logError($response->stderr());

    }

    public function saveFile($filename){
        $this->save($filename);
    }
    public function saveDirectory($filename){
        $this->save($filename);
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
     * @param \Tradesy\Innobackupex\Backup\Info $info
     * @param $filename
     */
    public function saveBackupInfo(\Tradesy\Innobackupex\Backup\Info $info, $filename)
    {
        // create temp file
        $response = $this->connection->executeCommand("mktemp", true);
        $file = $response->stdout();
        $serialized = serialize($info);

        $response = $this->connection->writeFileContents("$file", $serialized);
        $command = $this->binary .
            " s3 cp $file s3://" . $this->bucket .
            DIRECTORY_SEPARATOR .
            $filename;
        $this->logDebug("Upload latest backup info to S3 with command: $command \n");

        $response = $this->connection->executeCommand($command, true);
        $this->logDebug($response->stdout());
        $this->logError($response->stderr());
        $command = $this->binary .
            " s3 cp $file s3://" . $this->bucket .
            DIRECTORY_SEPARATOR .
            $info->getRepositoryBaseName() .
            DIRECTORY_SEPARATOR .
            "$filename";
        $response = $this->connection->executeCommand($command, true);
        $this->logDebug($response->stdout());
        $this->logError($response->stderr());
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