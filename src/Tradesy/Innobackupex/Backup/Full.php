<?php

namespace Tradesy\Innobackupex\Backup;
use \Tradesy\Innobackupex\Backup\AbstractBackup;
use \Tradesy\Innobackupex\Backup\Info;


class Full extends AbstractBackup
{

    protected $save_directory_prefix = "full_backup_";
    
    public function setRelativebackupdirectory()
    {
        $this->relative_backup_directory = $this->getSaveDirectoryPrefix() . 
                date("m-j-Y--H-i-s", $this->getStartDate());
    }

    public function PerformBackup()
    {
        $user = $this->getMysqlConfiguration()->getUsername();
        $password  = $this->getMysqlConfiguration()->getPassword();
        $host = $this->getMysqlConfiguration()->getHost();
        $port = $this->getMysqlConfiguration()->getPort();
        $directory = $this->getFullPathToBackup();
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
        $this->BackupInfo->setBaseBackupDirectory($this->getBasebackupDirectory());
        $this->BackupInfo->setLatestFullBackup($this->getFullPathToBackup());
        $this->BackupInfo->setIncrementalBackups(array());
        $this->BackupInfo->setRepositoryBaseName(date("m-j-Y--H-i-s", $this->getStartDate()));
        $this->writeFile($this->getBasebackupDirectory() . DIRECTORY_SEPARATOR . $this->getBackupInfoFilename(), serialize($this->BackupInfo), 0644);

    }

}
