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
use Tradesy\Innobackupex\Exceptions\MySQLDirectoryExistsException;

/**
 * Class AbstractBackup
 * @package Tradesy\Innobackupex\Backup
 */
class Mysql
{
    use \Tradesy\Innobackupex\Traits;

    /**
     * @var string
     */
    protected $prepare_flag_file = "already_prepared";
    /**
     * @var Configuration
     */
    protected $mysql_configuration;
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
     * @var int timestamp
     */
    protected $start_date;
    /**
     * @var int timestamp
     */
    protected $end_date;


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
    ) {
        $this->mysql_configuration = $mysql_configuration;
        $this->connection = $connection;
        $this->load_modules = $load_modules;
        $this->encryption_configuration = $enc_config;
        $this->compressed = $compressed;
        $this->memory = $memory;
    }

    public function directoryOrFileExists($directory){
        if($this->getConnection()->file_exists($directory)){
            return true;
        }
        return false;
    }
    public function loadBackupDirectory($directory){
        $base_dir = $this->BackupInfo->getBaseBackupDirectory();
        $full_dir = $base_dir . DIRECTORY_SEPARATOR . $directory;
        if($this->directoryOrFileExists($full_dir)){
            echo " full directory found " .$full_dir . "\n";
        }else{
            // use loadmodules to restore this backup
            $this->loadBackupDirectoryFromModules($directory);
        }
    }
    public function loadBackupDirectoryFromModules($directory){
        foreach($this->load_modules as $module){
            $module->load($this->BackupInfo, $directory);
            if($this->directoryOrFileExists($directory)) // don't proceed to next module since already loaded
                break;
        }
    }
    public function runRestore()
    {
        $base_dir = $this->BackupInfo->getBaseBackupDirectory();
        
        // First Check if /var/lib/mysql exists, otherwise script would fail at end
        if ($this->getConnection()->file_exists($this->getMysqlConfiguration()->getDataDirectory())) {
            throw new MySQLDirectoryExistsException(
                "
                
                Error: Data Directory (" . $this->getMysqlConfiguration()->getDataDirectory() . ") already exists.  
                    Please remove before running this script",
                0
            );
        }
        /*
         * Get all directories associated with complete backup (full + incrementals) in chronological order
         */
        $dirs = array_merge(
            $this->BackupInfo->getIncrementalBackups(),
            [$this->BackupInfo->getLatestFullBackup()]
        );
        /*
         * Ensure backups are present on server
         *  by this point by attempting to download via load_modules unless already present
         */
        foreach($dirs as $dir){
            $this->loadBackupDirectory($dir);
        }
        // add base directory prefix to path
        foreach($dirs as $dir){
            $full_dirs[] = $base_dir . DIRECTORY_SEPARATOR . $dir;
        }
        /*
         * Decrypt and decompress if necessary
         */
        $this->decryptAndDecompressBackups($full_dirs);
        
        /*
         * Apply log to full backup first with --redo-only flag
         */
        $full_backup = $this->BackupInfo->getLatestFullBackup();
        $this->ApplyLogIfNecessary($full_backup, "", true);

        $incrementals_array = $this->BackupInfo->getIncrementalBackups();
        $latest_incremental = $this->BackupInfo->getLatestIncrementalBackup();

        /*
         * Apply log with redo-only flag to all incrementals except the last
         */
        foreach ($incrementals_array as $incremental) {
            // the latest incremental requires apply log, but WITHOUT --redo-only
            $this->ApplyLogIfNecessary(
                $this->BackupInfo->getLatestFullBackup(),
                $incremental,
                $incremental != $latest_incremental ? true : false
            );
        }

        /*
         * Finally Copy Back the directory
         */ 
        echo "Copying Back mysql backup\n";
        $this->CopyBack($base_dir . DIRECTORY_SEPARATOR . $this->BackupInfo->getLatestFullBackup());

        /*
         * Don't forget to chown the directory
         */
        echo "Chowning mysql directory \n";
        $this->ChownDirectory();
        
        /*
         * The end.
         */
    }

    protected function IsDirectoryAlreadyPrepared($directory)
    {
        return $this->getConnection()->file_exists($directory . DIRECTORY_SEPARATOR . $this->prepare_flag_file);
    }

    protected function MarkDirectoryAsPrepared($directory)
    {
        echo "writing $directory . DIRECTORY_SEPARATOR . $this->prepare_flag_file";
        $this->getConnection()->writeFileContents($directory . DIRECTORY_SEPARATOR . $this->prepare_flag_file, "");
    }

    public function ApplyLogIfNecessary($base_dir, $inc_dir = "", $redo = true)
    {

        $base_backup_dir = $this->BackupInfo->getBaseBackupDirectory();
        $full_backup_full_path = $base_backup_dir . DIRECTORY_SEPARATOR . $base_dir;
        $inc_backup_full_path = $base_backup_dir . DIRECTORY_SEPARATOR . $inc_dir;
        if($inc_dir == ""
            && $this->IsDirectoryAlreadyPrepared($full_backup_full_path)){
            // full backup and already prepared
            echo "\nfull backup and already prepared\n";
            return;
        }
        if($inc_dir != "" && $this->IsDirectoryAlreadyPrepared($inc_backup_full_path)){
            echo "\n incremental backup and already prepared\n";
            return;
        }
        $command = "innobackupex " .
            " --apply-log " .
            " --use-memory=" . $this->getMemoryLimit() .
            ($redo ? " --redo-only " : " ") .
            $full_backup_full_path .
            (strlen($inc_dir)
                ? " --incremental-dir=" . $inc_backup_full_path
                : "");

        echo "Backup Command: $command \n";
        $response = $this->connection->executeCommand(
            $command
        );

        echo $response->stdout() . "\n";
        echo $response->stderr() . "\n";
        // Mark backup
        echo "Marking backup as already prepared \n";
        $this->MarkDirectoryAsPrepared((strlen($inc_dir)? $inc_backup_full_path : $full_backup_full_path));
    }

    protected function CopyBack($base_dir)
    {
        $command = "innobackupex" .
            " --copy-back $base_dir ";

        echo "Running Command: " . $command . "\n";
        $response = $this->getConnection()->executeCommand($command);

        echo $response->stdout() . "\n";
        echo $response->stderr() . "\n";
    }

    protected function ChownDirectory()
    {
        $command = "chown -R " .
            $this->getMysqlConfiguration()->getDataOwner() .
            "." .
            $this->getMysqlConfiguration()->getDataGroup() .
            " " .
            $this->getMysqlConfiguration()->getDataDirectory();

        // Defaults to chown -R mysql.mysql /var/lib/mysql

        $response = $this->getConnection()->executeCommand($command);

        echo $response->stdout() . "\n";
        echo $response->stderr() . "\n";
    }

    public function fetchBackupInfo()
    {
        foreach ($this->load_modules as $loadModule) {

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
            } catch (Exception $e) {
                // loading info failed from this module
            }
        }

        return $this->BackupInfo;
    }


}
