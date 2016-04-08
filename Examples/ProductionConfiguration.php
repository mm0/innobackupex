<?php

require_once __DIR__."/../../vendor/autoload.php";

$config = [
    'mysql_innobackupex_backup' => [
        'ssh' => [
            'host' => '127.0.0.1',
            'port' => 22,
            'user' => 'youcanbutshouldntuseroot',
            'public_key' => '/path/to/.ssh/private_key.pub',
            'private_key' => '/path/to/.ssh/private_key'
        ],
        'mysql' => [
            'host' =>  "127.0.0.1", /* "localhost" (non-IP) uses unix socket */
            'user' => "user",
            'password' =>"password",
            'port'  => 3306
        ],
        'encryption' => [
            'algorithm' => "AES256",
            'key' => "rPz2INPMV84MMzr5b8v1/Y900LCSJDFg"
        ],
        's3' => [
            'bucket' => 'my-encrypted-s3-mysql-backup-repository',
            'region' => 'us-east-1',
            'concurrency' => 100
        ],
        'gcs' => [
            'bucket' => 'my-encrypted-gcs-mysql-backup-repository',
            'region' => 'us-central1',
            'concurrency' => 100
        ]
    ]
];

// Change connection settings here

$mysql_config = new \Tradesy\Innobackupex\MySQL\Configuration (
    $config['host'],
    $config['user'],
    $config['password'],
    $config['port']
);

$encryption_configuration = new \Tradesy\Innobackupex\Encryption\Configuration(
    $config['algorithm'],
    $config['key']
);
