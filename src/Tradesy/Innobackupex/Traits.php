<?php
/**
 * Created by Matt Margolin.
 * Date: 3/28/16
 * Time: 1:29 PM
 */
namespace Tradesy\Innobackupex;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

trait Traits
{
    use LoggingTraits;
    
    function decryptAndDecompressBackups($backups)
    {
        $class = "\Tradesy\Innobackupex\Encryption\Configuration";

        $decryption_string = (($this->getEncryptionConfiguration() instanceof $class) ?
            $this->getEncryptionConfiguration()->getDecryptConfigurationString() : "");

        foreach ($backups as $basedir) {
            $this->logTrace("\n\n PROCESSING: " . $basedir . " \n\n\n");
            /*
             * Next we have to check if files are encrpyted,
             */

            /*
             * If compressed and encrypted, decrypt first
             */
            if ($this->decryptionRequired($basedir)
            ) {
                $command = "innobackupex " .
                    $decryption_string .
                    " --parallel " . $this->parallel_threads .
                    " $basedir";
                $this->logDebug( "Decrypting command: " . $command);
                $response = $this->getConnection()->executeCommand($command,true);

                $this->logDebug($response->stdout() . "\n");
                $this->logDebug($response->stderr() . "\n");
            }

            // If compressed, decompress
            if ($this->decompressionRequired($basedir)
            ) {
                $command = "innobackupex " .
                    " --decompress" .
                    " --parallel " . $this->parallel_threads .
                    " $basedir";
                $this->logDebug( "Decompressing command: " . $command);
                $response = $this->getConnection()->executeCommand($command,true);

                $this->logDebug($response->stdout() . "\n");
                $this->logDebug($response->stderr() . "\n");
            }

        }
    }
    public function decryptionRequired($directory){
        $files = $this->getConnection()->scandir($directory);
        $pattern = '/.*\.xbcrypt$/';
        $matches = preg_grep($pattern,$files);
        $do_these_files_exist = str_replace(".xbcrypt", "" , $matches);
        foreach($do_these_files_exist as $file){
            if(!in_array($file,$files))
                return true;
        }
        return false;
    }

    public function decompressionRequired($directory){
        $files = $this->getConnection()->scandir($directory);
        $pattern = '/.*\.qp$/';
        $matches = preg_grep($pattern,$files);
        $do_these_files_exist = str_replace(".qp", "" , $matches);
        foreach($do_these_files_exist as $file){
            if(!in_array($file,$files))
                return true;
        }
        return false;
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
     * @return MySQL\Configuration
     */
    public function getMysqlConfiguration()
    {
        return $this->mysql_configuration;
    }

    /**
     * @param MySQL\Configuration $mysql_configuration
     */
    public function setMysqlConfiguration(MySQL\Configuration $mysql_configuration)
    {
        $this->mysql_configuration = $mysql_configuration;
    }

    /**
     * @return Backup\Info
     */
    public function getBackupInfo()
    {
        return $this->BackupInfo;
    }

    /**
     * @param Backup\Info $BackupInfo
     */
    public function setBackupInfo(Backup\Info $BackupInfo)
    {
        $this->BackupInfo = $BackupInfo;
    }

    /**
     * @return Encryption\Configuration
     */
    public function getEncryptionConfiguration()
    {
        return $this->encryption_configuration;
    }

    /**
     * @param Encryption\Configuration $encryption_configuration
     */
    public function setEncryptionConfiguration(Encryption\Configuration $encryption_configuration)
    {
        $this->encryption_configuration = $encryption_configuration;
    }
}