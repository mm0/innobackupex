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

    /**
     * Begin the backup.
     */
    public function performBackup()
    {
        $user = $this->getMysqlConfiguration()->getUsername();
        $password = $this->getMysqlConfiguration()->getPassword();
        $host = $this->getMysqlConfiguration()->getHost();
        $port = $this->getMysqlConfiguration()->getPort();
        $directory = $this->getFullPathToBackup();
        $enc_class = "\Tradesy\Innobackupex\Encryption\Configuration";

        $command =
            "innobackupex" .
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
            " " . $directory;

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
        $this->fetchBackupInfo();
        $this->logInfo("Backup info saved to home directory");
        $enc_class = "\Tradesy\Innobackupex\Encryption\Configuration";
        $this->getBackupInfo()->setBaseBackupDirectory($this->getBaseBackupDirectory());
        $this->getBackupInfo()->setLatestFullBackup($this->getRelativeBackupDirectory());
        $this->getBackupInfo()->setIncrementalBackups(array());
        $this->getBackupInfo()->setRepositoryBaseName(date("m-j-Y--H-i-s", $this->getStartDate()));
        $this->getBackupInfo()->setEncrypted(($this->getEncryptionConfiguration() instanceof $enc_class) ? true : false);
        $this->getBackupInfo()->setCompression($this->getCompress());
        $this->writeFile($this->getBaseBackupDirectory() . DIRECTORY_SEPARATOR . $this->getBackupInfoFilename(),
            serialize($this->getBackupInfo()), 0644);

    }

}
