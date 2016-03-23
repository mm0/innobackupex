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
    protected $incremental_backups = array();
    protected $latest_full_backup;
    
    public function __construct(
        $backup_directory,
        $latest_full_backup,
        $incremental_backups
    ){
        
    }
    
    protected function addIncrementalBackup($Backup){
        
    }
}