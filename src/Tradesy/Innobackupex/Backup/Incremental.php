<?php

namespace Tradesy\Innobackupex\Backup;

class Incremental extends AbstractBackup
{

    protected $save_directory_prefix = "full_backup_";

    public function setRelativebackupdirectory()
    {
        $this->relative_backup_directory = $this->getSaveDirectoryPrefix() .
            date("m-j-Y--H-i-s", $this->getStartDate());
    }

    /*
     * TODO: move this into a trait to be used my Restore\Mysql
     */
    protected function decryptAndDecompressBackups($backups){
        $class = "\Tradesy\Innobackupex\Encryption\Configuration";

        $decryption_string = (($this->getEncryptionConfiguration() instanceof $class) ?
            $this->getEncryptionConfiguration()->getDecryptConfigurationString() : "");

        foreach($backups as $basedir) {
            /*
             * Next we have to check if files are encrpyted
             */
            $xtrabackup_file = $basedir . DIRECTORY_SEPARATOR . "xtrabackup_checkpoints";

            /*
             * If compressed and encrypted, decrypt first
             */
            if (!$this->getConnection()->file_exists($xtrabackup_file) &&
                $this->getConnection()->file_exists($xtrabackup_file . ".xbcrypt")
            ) {
                $command = "innobackupex " .
                    $decryption_string .
                    " $basedir --parallel 10";

                $response = $this->getConnection()->executeCommand($command);

                echo $response->stdout() . "\n";
                echo $response->stderr() . "\n";
            }
            /*
             * Now if compressed, decompress
             * xtrabackup_checkpoints doesn't get compressed, so check with different file
             * such as xtrabackup_info
             */
            $xtrabackup_file = $basedir . DIRECTORY_SEPARATOR . "xtrabackup_info";
            if (!$this->getConnection()->file_exists($xtrabackup_file) &&
                $this->getConnection()->file_exists($xtrabackup_file . ".qp")
            ) {
                $command = "innobackupex " .
                    " --decompress" .
                    " --parallel 10" .
                    " $basedir";
                $response = $this->getConnection()->executeCommand($command);

                echo $response->stdout() . "\n";
                echo $response->stderr() . "\n";
            }

        }
    }
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
        $x = "\Tradesy\Innobackupex\Encryption\Configuration";


        $encryption_string = (($this->getEncryptionConfiguration() instanceof $x) ?
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
            " --no-timestamp " .
            ($this->getCompress() ? " --compress" : "") .
            $encryption_string .
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
