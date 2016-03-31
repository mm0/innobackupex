<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 3/28/16
 * Time: 1:29 PM
 */
namespace Tradesy\Innobackupex;

trait Traits
{
    function decryptAndDecompressBackups($backups)
    {
        $class = "\Tradesy\Innobackupex\Encryption\Configuration";

        $decryption_string = (($this->getEncryptionConfiguration() instanceof $class) ?
            $this->getEncryptionConfiguration()->getDecryptConfigurationString() : "");

        foreach ($backups as $basedir) {
            echo "\n\n PROCESSING: " . $basedir . " \n\n\n";
            /*
             * Next we have to check if files are encrpyted
             */
            $xtrabackup_file = $basedir . DIRECTORY_SEPARATOR . "xtrabackup_checkpoints";

            /*
             * TODO: Better detection of compressed/encryption
             */

            /*
             * If compressed and encrypted, decrypt first
             */
            if (!$this->getConnection()->file_exists($xtrabackup_file) &&
                $this->getConnection()->file_exists($xtrabackup_file . ".xbcrypt")
            ) {
                $command = "innobackupex " .
                    $decryption_string .
                    " --parallel " . $this->parallel_threads .
                    " $basedir";
                echo "Decrypting command: " . $command;
                $response = $this->getConnection()->executeCommand($command,true);

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
                    " --parallel " . $this->parallel_threads .
                    " $basedir";
                echo "Decompressing command: " . $command;
                $response = $this->getConnection()->executeCommand($command,true);

                echo $response->stdout() . "\n";
                echo $response->stderr() . "\n";
            }

        }
    }

    /**
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }


    public function getMemoryLimit()
    {
        return $this->memory_limit;
    }

    /**
     * @param mixed $memory_limit
     */
    public function setMemoryLimit($memory_limit)
    {
        $this->memory_limit = $memory_limit;
    }

    /**
     * @return Configuration
     */
    public function getMysqlConfiguration()
    {
        return $this->mysql_configuration;
    }

    /**
     * @param Configuration $mysql_configuration
     */
    public function setMysqlConfiguration($mysql_configuration)
    {
        $this->mysql_configuration = $mysql_configuration;
    }

    /**
     * @return \Tradesy\Innobackupex\Backup\Info
     */
    public function getBackupInfo()
    {
        return $this->BackupInfo;
    }

    /**
     * @param \Tradesy\Innobackupex\Backup\Info $BackupInfo
     */
    public function setBackupInfo($BackupInfo)
    {
        $this->BackupInfo = $BackupInfo;
    }

    /**
     * @return EncryptionConfiguration
     */
    public function getEncryptionConfiguration()
    {
        return $this->encryption_configuration;
    }

    /**
     * @param EncryptionConfiguration $encryption_configuration
     */
    public function setEncryptionConfiguration($encryption_configuration)
    {
        $this->encryption_configuration = $encryption_configuration;
    }
}