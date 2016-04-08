<?php

require __DIR__ . "/../../vendor/autoload.php";

require_once "ProductionConfiguration.php";

/*
 *
 *
 * Warning: Don't run this on your existing production database
 *
 *
 */


/*
 *
 * This overrides the $ssh_config included via ProductionConfiguration.
 * Never use the backup configuration as it will destroy production db
 *
 */

$connection = new \Tradesy\Innobackupex\LocalShell\Connection();
$connection->setSudoAll(true); // not required if using root user to ssh. user must have sudo privileges to use this

$aws_restore_module = new \Tradesy\Innobackupex\S3\Local\Download(
    $connection,
    $config['mysql_innobackupex_restore']['s3']['bucket'],
    $config['mysql_innobackupex_restore']['s3']['region'],
    $config['mysql_innobackupex_restore']['s3']['concurrency']
);

/*
 * Disable this on QA since we don't want to setup OAuth2 on QA
 *
$google_restore_module = new \Tradesy\Innobackupex\GCS\Remote\Download(
    $connection,
    $tradesy_config['mysql_innobackupex_restore']['gcs']['bucket'],
    $tradesy_config['mysql_innobackupex_restore']['gcs']['region'],
    $tradesy_config['mysql_innobackupex_restore']['gcs']['concurrency']
);
*/

$BackupInfo = unserialize($connection->getFileContents("/tmp/backups/tradesy_percona_backup_info"));

$Restore = new \Tradesy\Innobackupex\Restore\Mysql(
    $mysql_config,
    $connection,
    [$aws_restore_module],//,$google_restore_module],
    $encryption_configuration,                          // Encryption configuration or null
    $compressed = true,                                 // Specify whether to compress backup
    $parallel_threads = 100,                            // Parallel threads
    $memory = "10G"                                     // Specify RAM Usage
);
$Restore->setBackupInfo($BackupInfo);

/*
 *  if not using root user, need to chmod the backup directory
 */
$save_directory = "/tmp/backups";

if ($config['mysql_innobackupex_backup']['ssh']['user'] != 'root') {
    $response = $connection->executeCommand(
        "chown -R " . $config['mysql_innobackupex_backup']['ssh']['user'] .
        "." .
        $config['mysql_innobackupex_backup']['ssh']['user'] .
        " $save_directory"
    );
    echo $response->stdout();
    echo $response->stderr();
}

$Restore->runRestore();

# Delete old backups
$latest_create_date = $BackupInfo->getRepositoryBaseName();
$date = preg_replace('/((\d+)-(\d+)-(\d+)).*/', '$1', $latest_create_date);


# delete backups older than this date
$files_to_delete = array();
foreach (glob("$save_directory/full_backup*") as $filename) {
    if (!preg_match("/$date/", $filename)) {
        echo "$filename size " . filesize($filename) . "\n";
        $files_to_delete[] = $filename;
    }
}
foreach (glob("$save_directory/incremental_backup*") as $filename) {
    if (!preg_match("/$date/", $filename)) {
        echo "$filename size " . filesize($filename) . "\n";
        $files_to_delete[] = $filename;
    }
}
foreach ($files_to_delete as $filename) {
    debug("Deleting Old Backups: " . $filename);
    $connection->executeCommand("rm -rf $filename");
}
