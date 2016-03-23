<?php

namespace Tradesy\Innobackupex\Backup;
use \Tradesy\Innobackupex\Backup\AbstractBackup;
use \Tradesy\Innobackupex\Backup\Info;


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
        $x = "\Tradesy\Innobackupex\Encryption\Configuration";
        $command =
            "innobackupex" .
            " --user=" . $user .
            " --password=" . $password .
            " --host=" . $host .
            " --port=" . $port .
            " --no-timestamp" .
            ($this->getCompress() ? " --compress" : "") .
            (($this->getEncryptionConfiguration() instanceof $x) ?
                $this->getEncryptionConfiguration()->getConfigurationString() : "" ).
            " " . $directory ;

        echo "Backup Command: $command \n";
        $response = $this->getConnection()->executeCommand($command);

        echo $response->stdout() . "\n";
        echo $response->stderr() . "\n";
    }

    public function SaveBackupInfo()
    {
        echo "Backup info save to home directory\n";

        $this->BackupInfo = new Info(
            $this->getActualDirectory(),
            $this->getFullPathToBackup(),
            array()
        );

        $this->writeFile("/tmp/tradesy_percona_backup_info", serialize($this->BackupInfo), 0644);

    }

}
