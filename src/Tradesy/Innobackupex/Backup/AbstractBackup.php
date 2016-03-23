<?php

namespace Tradesy\Innobackupex\Backup;

use Tradesy\Innobackupex\MySQL\Configuration;
use Tradesy\Innobackupex\Encryption\Configuration as EncryptionConfiguration;
use Tradesy\Innobackupex\ConnectionInterface;
use Tradesy\Innobackupex\Exceptions\InnobackupexException;
use Tradesy\Innobackupex\SaveInterface;

abstract class AbstractBackup
{
    protected $save_name;
    protected $full_path_to_backup;
    protected $full_path_to_backup_directory;
    protected $save_directory;
    protected $save_directory_prefix = "";
    protected $compress;
    protected $save_type;
    /**
     * @var Configuration
     */
    protected $mysql_configuration;
    /**
     * @var ConnectionInterface
     */
    protected $connection;
    /**
     * @var \Tradesy\Innobackupex\SaveInterface[]
     */
    protected $save_module;
    /**
     * @var EncryptionConfiguration
     */
    protected $encryption_configuration;
    protected $date;
    protected $start_date;
    protected $end_date;
    protected $memory_limit;
    protected $actual_directory;
    protected $backup_info_filename = "tradesy_percona_backup_info";
    protected $backup_info_directory = "/tmp/";
    protected $BackupInfo;



    /**
     * AbstractBackup constructor.
     * @param Configuration $mysql_configuration
     * @param ConnectionInterface $connection
     * @param SaveInterface[] $save_module
     * @param EncryptionConfiguration $enc_config
     * @param bool $compress
     * @param string $memory
     * @param string $save_directory
     * @param string $save_directory_prefix
     */
    public function __construct(
        Configuration $mysql_configuration,
        ConnectionInterface $connection,
        array $save_modules,
        EncryptionConfiguration $enc_config        = null,
        $compress                       = false,
        $memory                         = "1G",
        $save_directory                 = "tmp",
        $save_directory_prefix          = "full_backup"
    ) {
        $this->mysql_configuration      = $mysql_configuration;
        $this->connection               = $connection;
        $this->save_module              = $save_modules;
        $this->encryption_configuration = $enc_config;
        $this->compress                 = $compress;
        $this->memory                   = $memory;
        $this->save_directory           = $save_directory;
        $this->save_directory_prefix    = $save_directory_prefix;

    }

    /**
     * @return string
     */
    public function getBackupInfoFilename()
    {
        return $this->backup_info_filename;
    }

    /**
     * @param string $backup_info_filename
     */
    public function setBackupInfoFilename($backup_info_filename)
    {
        $this->backup_info_filename = $backup_info_filename;
    }
    /**
     * @return string
     */
    public function getBackupInfoDirectory()
    {
        return $this->backup_info_directory;
    }

    /**
     * @param string $backup_info_filename
     */
    public function setBackupInfoDirectory($backup_info_directory)
    {
        $this->backup_info_directory = $backup_info_directory;
    }


    /**
     * @return string
     */
    public function getSaveDirectoryPrefix()
    {
        return $this->save_directory_prefix;
    }

    /**
     * @param string $save_directory_prefix
     */
    public function setSaveDirectoryPrefix($save_directory_prefix)
    {
        $this->save_directory_prefix = $save_directory_prefix;
    }

    /**
     * @return mixed
     */
    public function getFullPathToBackupDirectory()
    {
        return $this->full_path_to_backup_directory;
    }

    /**
     * @param mixed $full_path_to_backup_directory
     */
    public function setFullPathToBackupDirectory($full_path_to_backup_directory)
    {
        $this->full_path_to_backup_directory = $full_path_to_backup_directory;
    }

    /**
     * @return mixed
     */
    public function getFullPathToBackup()
    {
        return $this->full_path_to_backup;
    }

    /**
     * @param mixed $full_path_to_backup
     */
    public function setFullPathToBackup($full_path_to_backup)
    {
        $this->full_path_to_backup = $full_path_to_backup;
    }


    /**
     * @return boolean
     */
    public function isRemoveArchiveAfterS3()
    {
        return $this->remove_archive_after_s3;
    }

    /**
     * @param boolean $remove_archive_after_s3
     */
    public function setRemoveArchiveAfterS3($remove_archive_after_s3)
    {
        $this->remove_archive_after_s3 = $remove_archive_after_s3;
    }

    /**
     * @return mixed
     */
    public function getActualDirectory()
    {
        return $this->actual_directory;
    }

    /**
     * @return string
     */
    public function getMemoryLimit()
    {
        return $this->memory_limit;
    }

    /**
     * @param string $memory_limit
     */
    public function setMemoryLimit($memory_limit)
    {
        $this->memory_limit = $memory_limit;
    }


    /**
     * @return mixed
     */
    public function getSaveName()
    {
        return $this->save_name;
    }

    /**
     * @param mixed $save_name
     */
    abstract function setSaveName();

    /**
     * @return mixed
     */
    public function getSaveDirectory()
    {
        return $this->save_directory;
    }


    /**
     * @return boolean
     */
    public function getCompress()
    {
        return $this->compress;
    }

    /**
     * @param boolean $compress
     */
    public function setCompress($compress)
    {
        $this->compress = $compress;
    }

    /**
     * @return mixed
     */
    public function getSaveType()
    {
        return $this->save_type;
    }

    /**
     * @param mixed $save_type
     */
    public function setSaveType($save_type)
    {
        $this->save_type = $save_type;
    }


    /**
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }
    /**
     * @return Configuration
     */
    public function getMysqlConfiguration()
    {
        return $this->mysql_configuration;
    }
    /**
     * @return Configuration
     */
    public function getEncryptionConfiguration()
    {
        return $this->encryption_configuration;
    }
    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param mixed $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return mixed
     */
    public function getStartDate()
    {
        return $this->start_date;
    }

    /**
     * @param mixed $start_date
     */
    public function setStartDate($start_date)
    {
        $this->start_date = $start_date;
    }

    /**
     * @return mixed
     */
    public function getEndDate()
    {
        return $this->end_date;
    }

    /**
     * @param mixed $end_date
     */
    public function setEndDate($end_date)
    {
        $this->end_date = $end_date;
    }

    public function start()
    {
        $this->setStartDate(time());
        $this->actual_directory =
            $this->getSaveDirectory()
            . "/" . $this->getSaveDirectoryPrefix()
            . date("m-j-Y--H-i-s", $this->getStartDate());
        $this->setSaveName();
        $this->setS3Name();
        echo "\nStarting Backup: " . date("F j, Y, g:i a", $this->getStartDate()) . "\n";
    }

    public function end()
    {
        $this->setEndDate(time());
        echo "\nBackup Finished: " . date("F j, Y, g:i a", $this->getEndDate()) . "\n";

    }


    public function test_innobackupex_exist()
    {
        $response = $this->connection->executeCommand(
            "which innobackupex"
        );
        if (
            empty($response->stdout())
            ||
            !empty($response->stderr())
        ) {
            throw new InnobackupexException(
                'Innobackupex binary not found' .
                0
            );
        } else {
            echo 'Innobackupex located: ' . $response->stdout() . "\n";
        }
    }

    public function ApplyLog()
    {
        $command = "sudo innobackupex --apply-log --use-memory=" .
            $this->getMemoryLimit() .
            " " .
            $this->getSaveDirectory() .
            date("m-j-Y--H-i-s", $this->getStartDate());

        echo "Backup Command: $command \n";
        $response = $this->connection->executeCommand(
            $command
        );
    }


    public function Backup()
    {
        $this->test_innobackupex_exist();
        if ($this->getCompress()) {
         //  $this->test_s3cmd();
        }
        $this->start();
        $this->PerformBackup();
        /**
         * TODO: update $this->BackupInfo
         */
        //  $this->ApplyLog();
        echo "Saved to " . $this->actual_directory . "\n";
        foreach($this->save_module as $saveModule){

            $saveModule->save($this->getSaveName());
            /*
             * optionally store backup info with save modules
             */
            $saveModule->saveBackupInfo($this->BackupInfo);
            $saveModule->cleanup();
        }
        $this->PostHook();
        $this->end();
    }

    public function writeFile($dest, $contents, $mode = 0644)
    {
        $this->connection->writeFileContents($dest, $contents, $mode);
    }

    public function fetchBackupInfo()
    {
        $remote_file = $this->getBackupInfoDirectory() . $this->getBackupInfoFilename();

        if ($this->getConnection()->file_exists($remote_file)) {
            $file_contents = $this->getConnection()->getFileContents($remote_file);
            $this->BackupInfo = unserialize($file_contents, true);
        }else{
            $this->BackupInfo = new Info();
        }
        return $this->BackupInfo;
    }

    private function RemoveArchivedFile()
    {
        if ($this->isRemoveArchiveAfterS3()) {
            $command = "sudo rm -f " . $this->getFullPathToBackup();
            $response = $this->connection->executeCommand(
                $command
            );
        }
    }

    private function PostHook()
    {
        if ($this->isRemoveArchiveAfterS3()) {
            # delete compressed backup
            $this->RemoveArchivedFile();
        }
    }
    

    abstract function SaveBackupInfo();

    abstract function PerformBackup();
}
