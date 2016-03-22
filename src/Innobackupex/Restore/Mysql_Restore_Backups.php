<?php

/*
 *
 *  Reference: http://www.percona.com/doc/percona-xtrabackup/2.1/innobackupex/incremental_backups_innobackupex.html
 *
 *
 *
 */

class Mysql_Restore_Backups
{

    protected $incremental_backups = array();
    protected $full_backup;
    protected $memory_limit = "1G";

    /**
     * @return mixed
     */
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
     * @return array
     */
    public function getIncrementalBackups()
    {
        return $this->incremental_backups;
    }

    /**
     * @param array $incremental_backups
     */
    public function setIncrementalBackups($incremental_backups)
    {
        $this->incremental_backups = $incremental_backups;
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


    public function __construct($full_backup, $incremental_backups)
    {
        $this->setIncrementalBackups($incremental_backups);
        $this->setFullBackup($full_backup);

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
