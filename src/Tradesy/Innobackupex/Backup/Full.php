<?php

namespace Tradesy\Innobackupex\Backup;
use \Tradesy\Innobackupex\Backup\AbstractBackup;
use \Tradesy\Innobackupex\Backup\Info;


/**
 * Class Full
 * @package Tradesy\Innobackupex\Backup
 */
class Full extends AbstractBackup
{

    /**
     * @var string
     */
    protected $save_directory_prefix = "full_backup_";
    

    public function PerformBackup()
    {
        $user = $this->getMysqlConfiguration()->getUsername();
        $password  = $this->getMysqlConfiguration()->getPassword();
        $host = $this->getMysqlConfiguration()->getHost();
        $port = $this->getMysqlConfiguration()->getPort();
        $directory = $this->getFullPathToBackup();
        $enc_class = "\Tradesy\Innobackupex\Encryption\Configuration";
        /*
         * TODO: --compress-threads=
         * TODO: --parallel
         */
        $command =
            "innobackupex" .
            " --user=" . $user .
            " --password=" . $password .
            " --host=" . $host .
            " --port=" . $port .
            " --parallel " .
            " --no-timestamp" .
            ($this->getCompress() ? 
                " --compress  --compress-threads=" . $this->compress_threads : "") .
            (($this->getEncryptionConfiguration() instanceof $enc_class) ?
                $this->getEncryptionConfiguration()->getConfigurationString() .
                " --encrypt-threads=" . $this->encrypt_threads : "" ).
            " " . $directory ;

        echo "Backup Command: $command \n";
        $response = $this->getConnection()->executeCommand($command);

        echo $response->stdout() . "\n";
        echo $response->stderr() . "\n";
    }

    public function SaveBackupInfo()
    {
        echo "Backup info save to home directory\n";
        $enc_class = "\Tradesy\Innobackupex\Encryption\Configuration";
        $this->BackupInfo->setBaseBackupDirectory($this->getBasebackupDirectory());
        $this->BackupInfo->setLatestFullBackup($this->getRelativebackupdirectory());
        $this->BackupInfo->setIncrementalBackups(array());
        $this->BackupInfo->setRepositoryBaseName(date("m-j-Y--H-i-s", $this->getStartDate()));
        $this->BackupInfo->setEncrypted(($this->getEncryptionConfiguration() instanceof $enc_class)? true : false );
        $this->BackupInfo->setCompression($this->getCompress());
        $this->writeFile($this->getBasebackupDirectory() . DIRECTORY_SEPARATOR . $this->getBackupInfoFilename(), serialize($this->BackupInfo), 0644);

    }

}
