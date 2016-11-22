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

    /**
     * Begin the backup.
     */
    public function performBackup()
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

        $basedir = $this->getBackupInfo()->getBaseBackupDirectory() . DIRECTORY_SEPARATOR .
            (is_null($this->getBackupInfo()->getLatestIncrementalBackup()) ?
                $this->getBackupInfo()->getLatestFullBackup() :
                $this->getBackupInfo()->getLatestIncrementalBackup());

        $this->decryptAndDecompressBackups([$basedir]);

        $command = "innobackupex " .
            " --user=" . $user .
            " --password=" . $password .
            " --host=" . $host .
            " --port=" . $port .
            " --parallel " . $this->parallel_threads .
            " --no-timestamp" .
            ($this->getCompress() ?
                " --compress  --compress-threads=" . $this->compress_threads : "") .
            (($this->getEncryptionConfiguration() instanceof $enc_class) ?
                $this->getEncryptionConfiguration()->getConfigurationString() .
                " --encrypt-threads=" . $this->encrypt_threads : "") .
            " --incremental " .
            $this->getFullPathToBackup() .
            " --incremental-basedir=" .
            $basedir;

        $this->logInfo("Backup Command: $command");
        $response = $this->getConnection()->executeCommand($command);

        $this->logDebug($response->stdout());
        $this->logError($response->stderr());
    }

    /**
     * Save Backup Information to base backup directory for use by later incremental backups and restoration.
     */
    public function saveBackupInfo()
    {
        $this->logInfo("Backup info saved to home directory");
        $this->getBackupInfo()->addIncrementalBackup(
            $this->getRelativeBackupDirectory()
        );
        $this->writeFile(
            $this->getBaseBackupDirectory() . DIRECTORY_SEPARATOR .
            $this->getBackupInfoFilename(),
            serialize($this->getBackupInfo()), 0644
        );

    }
}
