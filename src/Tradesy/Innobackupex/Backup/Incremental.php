<?php

namespace Tradesy\Innobackupex\Backup;

/**
 * Class Incremental
 * @package Tradesy\Innobackupex\Backup
 */
class Incremental extends AbstractBackup
{
    /**
     * @var string
     */
    protected $save_directory_prefix = "full_backup_";
    
    public function PerformBackup()
    {
        /*
         * If there are incrementals, use the directory returned by array_pop,
         * else use the base backup directory
         */
        $user = $this->getMysqlConfiguration()->getUsername();
        $password = $this->getMysqlConfiguration()->getPassword();
        $host = $this->getMysqlConfiguration()->getHost();
        $port = $this->getMysqlConfiguration()->getPort();
        $enc_class = "\Tradesy\Innobackupex\Encryption\Configuration";


        $encryption_string = (($this->getEncryptionConfiguration() instanceof $enc_class) ?
            $this->getEncryptionConfiguration()->getConfigurationString() : "");

        $basedir = $this->BackupInfo->getBaseBackupDirectory() . DIRECTORY_SEPARATOR .
            (is_null($this->BackupInfo->getLatestIncrementalBackup()) ?
                $this->BackupInfo->getLatestFullBackup() :
                $this->BackupInfo->getLatestIncrementalBackup());

        $this->decryptAndDecompressBackups([$basedir]);
        
        $command = "innobackupex " .
            " --user=" . $user .
            " --password=" . $password .
            " --host=" . $host .
            " --port=" . $port .
            " --parallel 100" .
            " --no-timestamp" .
            ($this->getCompress() ?
                " --compress  --compress-threads=" . $this->compress_threads : "") .
            (($this->getEncryptionConfiguration() instanceof $enc_class) ?
                $this->getEncryptionConfiguration()->getConfigurationString() .
                " --encrypt-threads=" . $this->encrypt_threads : "" ).
            " --incremental " .
            $this->getFullPathToBackup() .
            " --incremental-basedir=" .
            $basedir;
        echo "Backup Command: $command \n";
        $response = $this->getConnection()->executeCommand($command);

        echo $response->stdout() . "\n";
        echo $response->stderr() . "\n";
    }

    public function SaveBackupInfo()
    {
        echo "Backup info save to home directory\n";
        $this->BackupInfo->addIncrementalBackup(
            $this->getRelativebackupdirectory()
        );
        $this->writeFile(
            $this->getBasebackupDirectory() . DIRECTORY_SEPARATOR . 
            $this->getBackupInfoFilename(),
            serialize($this->BackupInfo), 0644
        );

    }
}
