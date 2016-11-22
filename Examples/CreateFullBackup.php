<?php

require_once __DIR__ . "/../../vendor/autoload.php";

require_once "ProductionConfiguration.php";

/*
 * This should only be used for backups as you don't want to restore onto prod db!
 */

$config = [
    'mysql_innobackupex_backup' => [
        'ssh' => [
            'host' => '127.0.0.1',
            'port' => 22,
            'user' => 'youcanbutshouldntuseroot',
            'public_key' => '/path/to/.ssh/private_key.pub',
            'private_key' => '/path/to/.ssh/private_key'
        ],
        's3' => [
            'bucket' => 'my-encrypted-s3-mysql-backup-repository',
            'region' => 'us-west-1',
            'concurrency' => 16
        ],
        'gcs' => [
            'bucket' => 'my-encrypted-gcs-mysql-backup-repository',
            'region' => 'us-central1',
            'concurrency' => 16
        ]
    ]
];

$ssh_config = new \Tradesy\Innobackupex\SSH\Configuration (
    $config['mysql_innobackupex_backup']['ssh']['host'],
    $config['mysql_innobackupex_backup']['ssh']['port'],
    $config['mysql_innobackupex_backup']['ssh']['user'],
    $config['mysql_innobackupex_backup']['ssh']['public_key'],
    $config['mysql_innobackupex_backup']['ssh']['private_key'],
    '',             // ssh key passphrase
    array('hostkey' => 'ssh-rsa')
);
// Specify the connection for the backup to use (local or remote SSH)
$connection = new \Tradesy\Innobackupex\SSH\Connection($ssh_config);
$connection->setSudoAll(true); // not required if using root user to ssh. user must have sudo privileges to use this

// Specify the storage module for the backup to use (local or remote SSH)

$s3_save_module = new \Tradesy\Innobackupex\S3\Remote\Upload(
    $connection,
    $config['mysql_innobackupex_backup']['s3']['bucket'], 
    $config['mysql_innobackupex_backup']['s3']['region'],
    $config['mysql_innobackupex_backup']['s3']['concurrency']
);
$google_save_module = new \Tradesy\Innobackupex\GCS\Remote\Upload(
    $connection,
    $config['mysql_innobackupex_backup']['gcs']['bucket'],
    $config['mysql_innobackupex_backup']['gcs']['region'],
    $config['mysql_innobackupex_backup']['gcs']['concurrency']
);
$Backup = new \Tradesy\Innobackupex\Backup\Full(
    $mysql_config,
    $connection,
    [$s3_save_module, $google_save_module],     // Array of save modules, minimum one
    $encryption_configuration,                  // Encryption configuration or null
    $compress = true,                           // Specify whether to compress backup
    $compress_threads = 16,                    // Specify # threads for compression
    $parallel_threads = 16,                            // Specify # threads
    $encryption_threads = 16,                  // Specify # threads for encryption
    $memory = "4G",                             // Specify RAM Usage
    $save_directory = "/tmp/backups",           // Specify the directory used to save backup
    $save_directory_prefix = "full_backup_"     // Specify prefix for to call the full backup
);


$Backup->Backup();

$save_directory = $Backup->getBackupInfo()->getBaseBackupDirectory() . DIRECTORY_SEPARATOR .
    $Backup->getBackupInfo()->getLatestFullBackup();

$response = $connection->executeCommand(
    "chown -R " . $config['mysql_innobackupex_backup']['ssh']['user'] .
    "." .
    $config['mysql_innobackupex_backup']['ssh']['user'] .
    " $save_directory"
);
