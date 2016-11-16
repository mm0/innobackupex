<?php

namespace Tradesy\Innobackupex\S3\Local;

use Tradesy\Innobackupex\Backup\Info;
use Tradesy\Innobackupex\LoggingTraits;
use \Tradesy\Innobackupex\LoadInterface;
use \Tradesy\Innobackupex\ConnectionInterface;
use \Tradesy\Innobackupex\Exceptions\BucketNotFoundException;
use \Aws\S3\S3Client;

/**
 * Class Download
 * @package Tradesy\Innobackupex\S3\Local
 */
class Download implements LoadInterface
{
    use LoggingTraits;
    /**
     * @var S3Client
     */
    protected $client;
    /**
     * @var ConnectionInterface
     */
    protected $connection;
    /**
     * @var string
     */
    protected $bucket;
    /**
     * @var string
     */
    protected $region;
    /**
     * @var
     */
    protected $key;
    /**
     * @var int
     */
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
        $region,
        $concurrency = 10
    ) {
        $this->connection = $connection;
        $this->bucket = $bucket;
        $this->region = $region;
        $this->concurrency = $concurrency;
        $this->client = S3Client::factory([
            "region" => $this->region
        ]);
        $this->testSave();

    }

    /**
     * @throws BucketNotFoundException
     */
    public function testSave()
    {
        if (!$this->client->doesBucketExist($this->bucket)) {
            throw new BucketNotFoundException(
                "S3 bucket (" . $this->bucket . ")  not found in region (" .
                $this->region . ")",
                0
            );
        }
    }

    /**
     * @param Info $info
     * @param $filename
     */
    public function load(Info $info, $filename)
    {
        //$filename = $info->getLatestFullBackup();
        echo "downloading $filename\n\n";
        echo "saving to: "  . $info->getBaseBackupDirectory() . DIRECTORY_SEPARATOR  ."\n\n";
        try {
            $this->client->downloadBucket(
                $info->getBaseBackupDirectory() . DIRECTORY_SEPARATOR . $filename,
                $this->bucket,
                DIRECTORY_SEPARATOR . $info->getRepositoryBaseName() . DIRECTORY_SEPARATOR . $filename,
                [
                    "allow_resumable" => false,
                    "concurrency" => $this->concurrency,
                    "base_dir" => $info->getRepositoryBaseName() . DIRECTORY_SEPARATOR . $filename,
                    "debug" => true
                ]
            );
        }catch(\Exception $e){
            echo 'exception';
        }
        return;
    }

    /**
     *
     */
    public function cleanup()
    {
    }

    /**
     * @param $backup_info_filename
     */
    public function getBackupInfo($backup_info_filename)
    {

        $response = $this->connection->executeCommand($command);
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