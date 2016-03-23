<?php

namespace Tradesy\Innobackupex\Backup;
use \Tradesy\Innobackupex\Backup\AbstractBackup;


class Full extends AbstractBackup
{

    public function setSaveName()
    {
        $this->save_name = "full_backup_" . date("m-j-Y--H-i-s", $this->getStartDate());
    }

    public function setS3Name()
    {
        $this->s3_name = date("m-j-Y--H-i-s", $this->getStartDate()) . "/" . $this->getSaveName();
    }

    public function PerformBackup()
    {
        $user = $this->getMysqlConfiguration()->getUsername();
        $password  = $this->getMysqlConfiguration()->getPassword();
        $host = $this->getMysqlConfiguration()->getHost();
        $port = $this->getMysqlConfiguration()->getPort();
        $directory = $this->getActualDirectory();

        $command =
            "sudo innobackupex" .
            " --user=" . $user .
            " --password=" . $password .
            " --host=" . $host .
            " --port=" . $port .
            " --no-timestamp" .
            " " . $directory ;

        echo "Backup Command: $command \n";
        $response = $this->getConnection()->executeCommand($command);

        echo $response->stdout() . "\n";
        echo $response->stderr() . "\n";
    }

    public function SaveBackupInfo()
    {
        echo "Backup info save to home directory\n";

        $BackupInfo = array(
            "latest_full_backup" => $this->getActualDirectory(),
            "latest_full_backup_s3_directory" => date("m-j-Y--H-i-s", $this->getStartDate()) . "/",
            "latest_full_backup_s3_bucket" => $this->getS3Bucket(),
            "latest_full_backup_s3_full_path" => $this->getS3Bucket() . $this->getS3Name() . ".tar.gz",
            "latest_full_backup_create_datetime" => date("m-j-Y--H-i-s", $this->getStartDate()),
            "latest_full_backup_local_path" => $this->getFullPathToBackup(),
            "latest_incremental_backup" => array(),
            "incremental_backup_list" => array()
        );

        $this->writeFile("/tmp/tradesy_percona_backup_info", json_encode($BackupInfo), 0644);

        $command = "sudo s3cmd put /tmp/tradesy_percona_backup_info" . " " . $this->getS3Bucket() . "tradesy_percona_backup_info";
        echo "Upload latest backup info to S3 with command: $command \n";
        $commandOutput = exec($command);
        echo $commandOutput;
    }

    public function DeleteOldFullBackup()
    {
        $file = $this->BackupInfo['latest_full_backup'];
        if (strlen($file) > 10) {
            echo "Deleting Old Full Backups\n";
            $command = "sudo rm -rf $file";
            $response = $this->getConnection()->executeCommand(
                $command
            );
        }
    }

    public function DeleteIncrementalBackups($prefix)
    {
        echo "Deleting Old Incremental Backups\n";
        if (count($this->BackupInfo['latest_incremental_backup'])) {
            $command = "sudo rm -rf $prefix";
            $response = $this->getConnection()->executeCommand(
                $command
            );
        }
    }
}
