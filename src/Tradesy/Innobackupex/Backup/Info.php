<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 3/22/16
 * Time: 6:19 PM
 */
namespace Tradesy\Innobackupex\Backup;

class Info {
    protected $base_backup_directory;
    protected $incremental_backups = array();
    protected $latest_full_backup;
    protected $repository_base_name;

    public function __construct(
        $backup_directory = null,
        $latest_full_backup = null,
        $incremental_backups = array(),
        $repository_base_name = ""
    ){
        $this->base_backup_directory = $backup_directory;
        $this->latest_full_backup = $latest_full_backup;
        $this->incremental_backups = $incremental_backups;
        $this->repository_base_name = $repository_base_name;
    }
    /**
     * @param mixed $backup_directory
     */
    public function setBaseBackupDirectory($backup_directory)
    {
        $this->base_backup_directory = $backup_directory;
    }

    /**
     * @param array $incremental_backups
     */
    public function setIncrementalBackups($incremental_backups)
    {
        $this->incremental_backups = $incremental_backups;
    }

    /**
     * @param mixed $latest_full_backup
     */
    public function setLatestFullBackup($latest_full_backup)
    {
        $this->latest_full_backup = $latest_full_backup;
    }

    /**
     * @return mixed
     */
    public function getBaseBackupDirectory()
    {
        return $this->base_backup_directory;
    }

    /**
     * @return string
     */
    public function getRepositoryBaseName()
    {
        return $this->repository_base_name;
    }

    /**
     * @param string $repository_base_name
     */
    public function setRepositoryBaseName($repository_base_name)
    {
        $this->repository_base_name = $repository_base_name;
    }

    /**
     * @return array
     */
    public function getIncrementalBackups()
    {
        return $this->incremental_backups;
    }
    /**
     * @return string
     */
    public function getLatestIncrementalBackup()
    {
        $backup = $this->incremental_backups;
        $latest = array_pop($this->incremental_backups);
        $this->incremental_backups = $backup;
        return $latest;
    }
    /**
     * @return mixed
     */
    public function getLatestFullBackup()
    {
        return $this->latest_full_backup;
    }
    
    public function addIncrementalBackup($Backup){
        $this->incremental_backups[] = $Backup;
    }
}