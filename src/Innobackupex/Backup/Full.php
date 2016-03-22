<?php

namespace Tradesy\Innobackupex\Backup;
use \Tradesy\Innobackupex\Backup\Abstract;


class Full extends Abstract
{

    protected $type = "FULL";

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
        $command = "sudo innobackupex --no-timestamp " . $this->getActualDirectory();
        echo "Backup Command: $command \n";
        $stream = ssh2_exec($this->SSH_Connection, $command);
        $stderrStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
        stream_set_blocking($stderrStream, true);
        stream_set_blocking($stream, true);
        $stdout = stream_get_contents($stream);
        $stderr = stream_get_contents($stderrStream);

        echo $stdout . "\n";
        echo $stderr . "\n";
        fclose($stream);
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
            $response = $this->connection->executeCommand(
                $command
            );
        }
    }

    public function DeleteIncrementalBackups($prefix)
    {
        echo "Deleting Old Incremental Backups\n";
        if (count($this->BackupInfo['latest_incremental_backup'])) {
            $command = "sudo rm -rf $prefix";
            $response = $this->connection->executeCommand(
                $command
            );
        }
    }
}
