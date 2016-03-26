<?php

/*
 *
 *  Reference: http://www.percona.com/doc/percona-xtrabackup/2.1/innobackupex/incremental_backups_innobackupex.html
 *
 *
 *
 */
namespace Tradesy\Innobackupex\Restore;
use Tradesy\Innobackupex\MySQL\Configuration;
use Tradesy\Innobackupex\Encryption\Configuration as EncryptionConfiguration;
use Tradesy\Innobackupex\ConnectionInterface;
use Tradesy\Innobackupex\Exceptions\InnobackupexException;

/**
 * Class AbstractBackup
 * @package Tradesy\Innobackupex\Backup
 */
class Mysql
{
    /**
     * @var Configuration
     */
    protected $mysql_configuration;

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
     * @var ConnectionInterface
     */
    protected $connection;
    /**
     * @var bool
     */
    protected $compressed;
    /**
     * @var \Tradesy\Innobackupex\Backup\Info
     */
    protected $BackupInfo;    
    /**
     * @var string
     * @desc Max memory to use during backup ie. "1G" for 1 gigabyte
     */
    protected $memory_limit = "1G";
    /**
     * @var EncryptionConfiguration
     */
    protected $encryption_configuration;
    /**
     * @var \Tradesy\Innobackupex\LoadInterface[]
     */
    protected $load_modules;
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
    /**
     * @var int timestamp
     */
    protected $start_date;
    /**
     * @var int timestamp
     */
    protected $end_date;
    
    
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
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }


    /**
     * Restore constructor.
     * @param Configuration $mysql_configuration
     * @param ConnectionInterface $connection
     * @param LoadInterface[] $load_modules
     * @param EncryptionConfiguration $enc_config
     * @param bool $compressed
     * @param string $memory
     * @param string $base_backup_directory
     * @param string $save_directory_prefix
     */
    public function __construct(
        Configuration $mysql_configuration,
        ConnectionInterface $connection,
        array $load_modules,
        EncryptionConfiguration $enc_config = null,
        $compressed = false,
        $memory = "1G",
        $base_backup_directory = "/tmp",
        $save_directory_prefix = "full_backup"
    ){
        $this->mysql_configuration = $mysql_configuration;
        $this->connection = $connection;
        $this->load_modules = $load_modules;
        $this->encryption_configuration = $enc_config;
        $this->compressed = $compressed;
        $this->memory = $memory;
    }

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

    public function runRestore()
    {
        // First Check if /var/lib/mysql exists, otherwise script would fail at end

        if($this->getConnection()->file_exists("/var/lib/mysql")){
            throw new MySQLDirectoryExistsException(
                "Error: /var/lib/mysql already exists.  
                    Please remove before running this script",
                0
            );
        }
        /*
         * TODO: Ensure backups are present on server 
         *          by this point by attempting to download via load_modules unless already present
         */
        
        /*
         * get all directories associated with complete backup (full + incrementals)
         */
        $dirs = array_merge( 
            $this->BackupInfo->getIncrementalBackups(), 
            [$this->BackupInfo->getLatestFullBackup()]
        );

        /*
         * Decrypt and decompress if necessary
         */
        $this->decryptAndDecompressBackups($dirs);

        /*
         * TODO: Apply log to the full backup unless already prepared
         */
       // if(!$this->IsFullBackupAlreadyPrepared()) {
        $full_backup = $this->BackupInfo->getLatestFullBackup();
            $this->ApplyLogIf($full_backup, "", true);
       // }
        $incrementals_array = $this->BackupInfo->getIncrementalBackups();
        $latest_incremental = $this->BackupInfo->getLatestIncrementalBackup();

        foreach($incrementals_array as $incremental){
            // the latest incremental requires apply log, but WITHOUT--redo-only
            $this->ApplyLogIfNecessary(
                $this->getFullBackup(),
                $incremental,
                $incremental != $latest_incremental ? true : false
            );
        }

        // Finally Copy Back the directory
        echo "Copying Back mysql backup\n";
        $this->CopyBack($this->getFullBackup());

        // Don't forget to chown the directory
        echo "Chowning mysql directory \n";
        $this->ChownDirectory();



    }

    protected function IsFullBackupAlreadyPrepared(){
        return (file_exists($this->getFullBackup() . "/already_prepared")? true : false);
    }
    protected function MarkFullBackupAsPrepared(){
        exec("sudo touch " . $this->getFullBackup() . "/already_prepared");
    }

    public function ApplyLogIfNecessary($base_dir, $inc_dir = "", $redo = true)
    {
        /*
         * TODO: Check here whether this particular directory has been prepared
         */
        $command = "sudo innobackupex --apply-log --use-memory=" .
            $this->getMemoryLimit() . ($redo ? " --redo-only " : " ")
            . $base_dir . (strlen($inc_dir) ? " --incremental-dir=$inc_dir" : "");

        echo "Backup Command: $command \n";
        $response = $this->connection->executeCommand(
            $command
        );
        // Mark backup
        echo "Marking backup as already prepared \n";
        /*
         * TODO: make this work with incrementals as well
         */
        $this->MarkFullBackupAsPrepared();
    }
    protected function CopyBack($base_dir)
    {
        $command = "sudo innobackupex --copy-back $base_dir ";
        echo "Running Command: " . $command . "\n";
        $this->getConnection()->executeCommand($command);
    }

    protected function ChownDirectory()
    {
        exec("sudo chown -R mysql.mysql /var/lib/mysql");
    }
    public function fetchBackupInfo()
    {
        foreach ($this->load_modules  as $loadModule) {

            try {
                $loadModule->setKey(
                    $this->BackupInfo->getRepositoryBaseName() .
                    DIRECTORY_SEPARATOR .
                    $this->getRelativebackupdirectory()
                );
                $loadModule->load($this->BackupInfo);
                /*
                 * optionally store backup info with save modules
                 */
                $loadModule->getBackupInfo($this->getBackupInfoFilename());
                $loadModule->cleanup();
            }catch(Exception $e){
                // loading info failed from this module
            }
        }
        return $this->BackupInfo;
    }
}
