<?php

namespace Tradesy\Innobackupex\S3\Local;

use Aws\S3\Model\MultipartUpload\UploadBuilder;
use \Aws\S3\S3Client;
use Tradesy\Innobackupex\Backup\Info;
use Tradesy\Innobackupex\LoggingTraits;
use Tradesy\Innobackupex\SaveInterface;

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
     * Upload constructor.
     * @param ConnectionInterface $connection
     * @param S3Client $client
     * @param int $concurrency
     */
    public function __construct(ConnectionInterface $connection, S3Client $client, $concurrency = 10)
    {
        $this->connection = $connection;
        $this->client = $client;
        $this->concurrency = $concurrency;
    }

    /**
     *
     */
    public function testSave()
    {

    }

    /**
     * @param string $filename
     */
    public function save($filename)
    {
        UploadBuilder::newInstance()
            ->setClient($this->client)
            ->setSource($this->source)
            ->setBucket($this->bucket)
            ->setKey($this->key)
            ->setOption('CacheControl', 'max-age=3600')
            ->setConcurrency($this->concurrency)
            ->build();
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

        $response = $this->connection->writeFileContents("/tmp/temporary_backup_info", $serialized);
        $command = $this->binary . " s3 cp /tmp/temporary_backup_info s3://" . $this->bucket . "/tradesy_percona_backup_info";
        echo "Upload latest backup info to S3 with command: $command \n";

        $response = $this->connection->executeCommand($command);
        echo $response->stdout();
        echo $response->stderr();

    }

    /**
     * @param mixed $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }
}