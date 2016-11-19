<?php

namespace Tradesy\Innobackupex\GCS\Local;

use Tradesy\Innobackupex\Backup\Info;
use \Tradesy\Innobackupex\LoadInterface;
use \Tradesy\Innobackupex\ConnectionInterface;
use \Tradesy\Innobackupex\Exceptions\BucketNotFoundException;
use \Google;

/**
 * Class Download
 * @package Tradesy\Innobackupex\GCS\Local
 */
class Download implements LoadInterface
{

    /**
     * @var \Google_Client
     */
    protected $client;

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
     * Upload constructor.
     * @param $connection
     * @param $bucket
     * @param $region
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
        $this->client = new \Google_Client();
        $this->client->setApplicationName("My Application");
        $this->client->setDeveloperKey("MY_SIMPLE_API_KEY");

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
        $service = new \Google_Service_Storage($this->client);
        $object = $service->objects->get( $this->bucket, $filename )->toSimpleObject();
        $request = new \Google_Http_Request($object['mediaLink'], 'GET');
        $signed_request = $this->client->getAuth()->sign($request);
        $http_request = $this->client->getIo()->makeRequest($signed_request);
        echo $http_request->getResponseBody();


        $this->client->downloadBucket(
            $info->getBaseBackupDirectory() . DIRECTORY_SEPARATOR . $filename ,
            $this->bucket,
            DIRECTORY_SEPARATOR . $info->getRepositoryBaseName() . DIRECTORY_SEPARATOR . $filename,
            [
                "allow_resumable" => false,
                "concurrency" => $this->concurrency,
                "base_dir" => $info->getRepositoryBaseName(). DIRECTORY_SEPARATOR . $filename,
                "debug" => true
            ]);
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

        /*$response = $this->connection->executeCommand($command);
        echo $response->stdout();
        echo $response->stderr();*/

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