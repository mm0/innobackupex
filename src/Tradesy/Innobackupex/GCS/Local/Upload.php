<?php

namespace Tradesy\Innobackupex\GCS\Local;


use Tradesy\Innobackupex\SaveInterface;
use \Google;

/**
 * Class Upload
 * @package Tradesy\Innobackupex\GCS\Local
 */
class Upload implements SaveInterface
{
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
     * @var GCSClient
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
     * @var string
     */
    protected $binary = "gcloud";

    /**
     * Upload constructor.
     * @param ConnectionInterface $connection
     * @param GCSClient $client
     * @param int $concurrency
     */
    public function __construct(ConnectionInterface $connection,
                                $bucket,
                                $region,
                                $concurrency = 10
    )
    {
        $this->connection = $connection;
        $this->bucket = $bucket;
        $this->region = $region;
        $this->concurrency = $concurrency;
        $this->client = new \Google_Client();
    }

    /**
     *
     */
    public
    function testSave()
    {

    }

    /**
     * @param string $filename
     */
    public
    function save($filename)
    {
        $uploader = \UploadBuilder::newInstance()
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
    public
    function cleanup()
    {

    }

    /**
     *
     */
    public
    function verify()
    {

    }

    /**
     * @param \Tradesy\Innobackupex\Backup\Info $info
     * @param $filename
     */
    public
    function saveBackupInfo(\Tradesy\Innobackupex\Backup\Info $info, $filename)
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
    public
    function setKey($key)
    {
        $this->key = $key;
    }
}