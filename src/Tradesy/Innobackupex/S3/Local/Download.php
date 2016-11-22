<?php

namespace Tradesy\Innobackupex\S3\Local;

use Tradesy\Innobackupex\LogEntry;
use \Tradesy\Innobackupex\SSH\Connection;
use \Tradesy\Innobackupex\LoadInterface;
use \Tradesy\Innobackupex\ConnectionInterface;
use \Tradesy\Innobackupex\Exceptions\CLINotFoundException;
use \Tradesy\Innobackupex\Exceptions\BucketNotFoundException;
use \Aws\S3\S3Client;

class Download implements LoadInterface
{

    /**
     * @var \Aws\S3\S3Client
     */
    protected $client;

    protected $connection;
    protected $bucket;
    protected $region;
    protected $source;
    protected $key;
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

    public function load(\Tradesy\Innobackupex\Backup\Info $info, $filename)
    {
        //$filename = $info->getLatestFullBackup();
        LogEntry::logEntry('Downloading ' . $filename);
        LogEntry::logEntry('Saving to: '  . $info->getBaseBackupDirectory() . DIRECTORY_SEPARATOR);
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
            LogEntry::logEntry('Exception caught ' . $e->getMessage());
        }
        return;
    }

    public function cleanup()
    {
    }

    public function getBackupInfo($backup_info_filename)
    {

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