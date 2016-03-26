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
     * @var ConnectionInterface
     */
    protected $connection;
    /**
     * @var bool
     */
    protected $compressed;
    protected $full_backup;
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
     * @return mixed
     */
    public function getFullBackup()
    {
        return $this->full_backup;
    }

    /**
     * @param mixed $full_backup
     */
    public function setFullBackup($full_backup)
    {
        $this->full_backup = $full_backup;
    }


    /**
     * Restore constructor.
     * @param Configuration $mysql_configuration
     * @param ConnectionInterface $connection
     * @param LoadInterfaces[] $load_modules
     * @param EncryptionConfiguration $enc_config
     * @param bool $compressed
     * @param string $memory
     * @param string $base_backup_directory
     * @param string $save_directory_prefix
     */
    public function __construct(
        Configuration $mysql_configuration,
        ConnectionInterface $connection,
        array $load_moduless,
        EncryptionConfiguration $enc_config = null,
        $compressed = false,
        $memory = "1G",
        $base_backup_directory = "/tmp",
        $save_directory_prefix = "full_backup"
    ){
        $this->mysql_configuration = $mysql_configuration;
        $this->connection = $connection;
        $this->load_modules = $load_moduless;
        $this->encryption_configuration = $enc_config;
        $this->encryption_configuration = $enc_config;
        $this->compressed = $compressed;
        $this->memory = $memory;
    }

    public function runRestore()
    {
        // First Check if /var/lib/mysql exists, otherwise script would fail at end
        if(file_exists("/var/lib/mysql")){
            die("Error: /var/lib/mysql already exists.  Please remove before running this script");
        }

        // First Apply log with --redo-only option to base dir
        if (strlen($this->getFullBackup())) {
            if(!$this->IsFullBackupAlreadyPrepared()){
                $this->ApplyLog($this->getFullBackup(), "", true);


                // Next Apply log with --redo-only to all incremental backups except the last
                $incremental_backups = $this->getIncrementalBackups();
                for ($i = 0; $i < count($incremental_backups) - 1; $i++) {
                    $this->ApplyLog($this->getFullBackup(), $incremental_backups[$i], true);
                }

                // Now if there are incremental backups, apply log to the most recent one but WITHOUT--redo-only
                if (count($incremental_backups)) {
                    $this->ApplyLog($this->getFullBackup(), $incremental_backups[count($incremental_backups) - 1], false);

                }
            }


            // Finally Copy Back the directory
            echo "Copying Back mysql backup\n";
            $this->CopyBack($this->getFullBackup());

            // Don't forget to chown the directory
            echo "Chowning mysql directory \n";
            $this->ChownDirectory();

            // Mark full backup
            echo "Marking backup as already prepared \n";
            $this->MarkFullBackupAsPrepared();
        }
    }

    protected function IsFullBackupAlreadyPrepared(){
        return (file_exists($this->getFullBackup() . "/already_prepared")? true : false);
    }
    protected function MarkFullBackupAsPrepared(){
        exec("sudo touch " . $this->getFullBackup() . "/already_prepared");
    }
    public function ApplyLog($base_dir, $inc_dir = "", $redo = true)
    {
        $command = "sudo innobackupex --apply-log --use-memory=" .
            $this->getMemoryLimit() . ($redo ? " --redo-only " : " ")
            . $base_dir . (strlen($inc_dir) ? " --incremental-dir=$inc_dir" : "");
        echo "Running Command: " . $command . "\n";
        exec($command);
    }

    protected function CopyBack($base_dir)
    {
        $command = "sudo innobackupex --copy-back $base_dir ";
        echo "Running Command: " . $command . "\n";
        exec($command);
    }

    protected function ChownDirectory()
    {
        exec("sudo chown -R mysql.mysql /var/lib/mysql");
    }
}
