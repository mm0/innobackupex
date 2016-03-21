<?php

namespace Tradesy\Innobackupex;

use \Aws\S3\S3Client;
use \Aws\S3\MultipartUploader;
use \Aws\Exception\MultipartUploadException;

class S3 implements \Tradesy\Innobackupex\SaveInterface
{

    protected $bucket;
    protected $region;
    protected $client;
    protected $source;
    protected $key;
    protected $concurrency;

    public function __construct(S3Client $client, $concurrency = 10)
    {
        $this->client = $client;
        $this->concurrency = $concurrency;
    }

    public function testSave()
    {

    }

    public function save()
    {
        $uploader = UploadBuilder::newInstance()
            ->setClient($this->client)
            ->setSource($this->source)
            ->setBucket($this->bucket)
            ->setKey($this->key)
            ->setOption('CacheControl', 'max-age=3600')
            ->setConcurrency($this->concurrency)
            ->build();
    }

    public function cleanup()
    {

    }

    public function verify()
    {

    }
}