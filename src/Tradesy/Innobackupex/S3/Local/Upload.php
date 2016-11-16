<?php

namespace Tradesy\Innobackupex\S3\Local;

use Aws\S3\Model\MultipartUpload\UploadBuilder;
use \Aws\S3\S3Client;
use \Tradesy\Innobackupex\Backup\Info;
use \Tradesy\Innobackupex\LoggingTraits;
use \Tradesy\Innobackupex\SaveInterface;
use \Tradesy\Innobackupex\ConnectionInterface;
use \Tradesy\Innobackupex\Exceptions\BucketNotFoundException;

/**
 * Class Upload
 * @package Tradesy\Innobackupex\S3\Local
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
     * @var S3Client
     */
    protected $client;
    /**
     * @var
     */
    protected $key;
    /**
     * @var int
     */
    protected $concurrency;

    protected $debug = false;
    /**
     * Upload constructor.
     * @param ConnectionInterface $connection
     * @param S3Client $client
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
        $this->concurrency = $concurrency;
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
     * @param string $filename
     */
    public function saveFile($filename)
    {
        UploadBuilder::newInstance()
            ->setClient($this->client)
            ->setSource($filename)
            ->setBucket($this->bucket)
            ->setKey($this->key )
            ->setOption('CacheControl', 'max-age=3600')
            ->setConcurrency($this->concurrency)
            ->build();
    }
    /**
     * @param string $filename
     */
    public function saveDirectory($filename)
    {
        $this->client->uploadDirectory($filename, $this->bucket, $this->key, array(
            'concurrency' => $this->concurrency,
            'debug'       => $this->debug
        ));
    }
    public function save($filename){
        $this->saveDirectory($filename);
    }
    /**
     *
     */
    public function cleanup()
    {

    }

    /**
     *
     */
    public function verify()
    {

    }

    /**
     * @param Info $info
     * @param $filename
     */
    public function saveBackupInfo(Info $info, $filename)
    {
        $serialized = serialize($info);
        $filename = strlen($filename) ? $filename : $this->connection->getTemporaryDirectoryPath()."temporary_backup_info";
        $response = $this->connection->writeFileContents($filename, $serialized);
        $this->logDebug("Upload latest backup info to S3 with SDK");
        $this->saveFile($filename);
    }

    /**
     * @param mixed $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }
}