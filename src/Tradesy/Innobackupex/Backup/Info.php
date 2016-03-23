<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 3/22/16
 * Time: 6:19 PM
 */
namespace Tradesy\Innobackupex\Backup;

class Info {
    protected $backup_directory;

    /**
     * @return mixed
     */
    public function getBackupDirectory()
    {
        return $this->backup_directory;
    }

    /**
     * @return array
     */
    public function getIncrementalBackups()
    {
        return $this->incremental_backups;
    }

    /**
     * @return mixed
     */
    public function getLatestFullBackup()
    {
        return $this->latest_full_backup;
    }
    protected $incremental_backups = array();
    protected $latest_full_backup;
    
    public function __construct(
        $backup_directory = null,
        $latest_full_backup = null,
        $incremental_backups = array()
    ){
        
    }
    
    protected function addIncrementalBackup($Backup){
        
    }
}