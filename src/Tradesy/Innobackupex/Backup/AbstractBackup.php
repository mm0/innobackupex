<?php

namespace Tradesy\Innobackupex\Backup;

use Tradesy\Innobackupex\Backup\Info;
use Tradesy\Innobackupex\MySQL\Configuration;
use Tradesy\Innobackupex\Encryption\Configuration as EncryptionConfiguration;
use Tradesy\Innobackupex\ConnectionInterface;
use Tradesy\Innobackupex\Exceptions\InnobackupexException;
use Tradesy\Innobackupex\SaveInterface;
use Tradesy\Innobackupex\Traits;

/**
 * Class AbstractBackup
 * @package Tradesy\Innobackupex\Backup
 */
abstract class AbstractBackup
{
    use Traits;

    /**
     * @var string
     * @desc Directory name relative to $save_directory
     */
    protected $relative_backup_directory;
    /**
     * @var string
     * The directory where any backup directories will live
     */
    protected $base_backup_directory;
    /**
     * @var string
     * @desc Prefix for relative backup directory
     */
    protected $save_directory_prefix;
    /**
     * @var bool
     */
    protected $compress;
    /**
     * @var int
     */
    protected $compress_threads;
    /**
     * @var int
     */
    protected $encrypt_threads;
    /**
     * @var int
     */
    protected $parallel_threads;
    /**
     * @var Configuration
     */
    protected $mysql_configuration;
    /**
     * @var ConnectionInterface
     */
    protected $connection;
    /**
     * @var SaveInterface[]
     */
    protected $save_modules;
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
    /**
     * @var string
     * @desc Max memory to use during backup ie. "1G" for 1 gigabyte
     */
    protected $memory_limit = "1G";
    /**
     * @var string
     * @desc Filename to store serialized backup information
     */
    protected $backup_info_filename = "tradesy_percona_backup_info";
    /**
     * @var Info
     */
    protected $BackupInfo;

    protected $array_of_bad_words = array();
    /**
     * AbstractBackup constructor.
     * @param Configuration $mysql_configuration
     * @param ConnectionInterface $connection
     * @param SaveInterface[] $save_module
     * @param EncryptionConfiguration $enc_config
     * @param bool $compress
     * @param int $compress_threads
     * @param int $parallel_threads
     * @param int $encrypt_threads
     * @param string $memory
     * @param string $base_backup_directory
     * @param string $save_directory_prefix
     */
    public function __construct(
        Configuration $mysql_configuration,
        ConnectionInterface $connection,
        array $save_modules,
        EncryptionConfiguration $enc_config = null,
        $compress = false,
        $compress_threads = 100,
        $parallel_threads = 100,
        $encrypt_threads = 100,
        $memory = "1G",
        $base_backup_directory = "/tmp",
        $save_directory_prefix = "full_backup"
    ) {
        $this->mysql_configuration = $mysql_configuration;
        $this->connection = $connection;
        $this->save_modules = $save_modules;
        $this->encryption_configuration = $enc_config;
        $this->compress = $compress;
        $this->compress_threads = $compress_threads;
        $this->parallel_threads = $parallel_threads;
        $this->encrypt_threads = $encrypt_threads;
        $this->memory = $memory;
        $this->base_backup_directory = $base_backup_directory;
        $this->save_directory_prefix = $save_directory_prefix;
        $this->mysql_configuration->verify();
        $this->array_of_bad_words[] = $this->getMysqlConfiguration()->getPassword();
        $this->array_of_bad_words[] = $this->getEncryptionConfiguration()->getEncryptionKey();
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
    public function getFullPathToBackup()
    {
        return $this->getBaseBackupDirectory() . DIRECTORY_SEPARATOR . $this->getRelativeBackupDirectory();
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
     * @return string
     */
    public function getRelativeBackupDirectory()
    {
        return $this->relative_backup_directory;
    }

    /**
     * @return mixed
     */
    public function getBaseBackupDirectory()
    {
        return $this->base_backup_directory;
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
     * @return EncryptionConfiguration
     */
    public function getEncryptionConfiguration()
    {
        return $this->encryption_configuration;
    }

    /**
     * timestamp
     * @return int
     */
    public function getStartDate()
    {
        return $this->start_date;
    }

    /**
     * timestamp
     * @param int $start_date
     */
    public function setStartDate($start_date)
    {
        $this->start_date = $start_date;
    }

    /**
     * timestamp
     * @return int
     */
    public function getEndDate()
    {
        return $this->end_date;
    }

    /**
     * timestamp
     * @param int $end_date
     */
    public function setEndDate($end_date)
    {
        $this->end_date = $end_date;
    }

    /**
     * Sets start time using current timestamp
     * Sets Relative Backup Directory
     */
    public function start()
    {
        $this->setStartDate(time());
        $this->setRelativeBackupDirectory();
        $this->logInfo("Starting Backup: " . date("F j, Y, g:i a", $this->getStartDate()));
    }

    /**
     * Sets end time using current timestamp
     */
    public function end()
    {
        $this->setEndDate(time());
        $this->logInfo("Backup Finished: " . date("F j, Y, g:i a", $this->getEndDate()));

    }

    /**
     * @throws InnobackupexException
     */
    public function checkInnobackupexBinaryInstalled()
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
            $this->logInfo('Innobackupex located: ' . $response->stdout() . "\n");
        }
    }


    /**
     * @throws InnobackupexException
     */
    public function Backup()
    {
        $this->checkInnobackupexBinaryInstalled();
        $this->start();
        $this->performBackup();
        $this->getConnection()->recursivelyChownDirectory(
            $this->getFullPathToBackup(),
            $this->mysql_configuration->getDataOwner(),
            $this->mysql_configuration->getDataGroup(),
            775
        );
        $this->saveBackupInfo();
        $this->logInfo("Saved to " . $this->getFullPathToBackup() . "\n");
        foreach ($this->save_modules as $saveModule) {
            $saveModule->setKey(
                $this->BackupInfo->getRepositoryBaseName() .
                DIRECTORY_SEPARATOR .
                $this->getRelativeBackupDirectory()
            );
            $saveModule->save($this->getFullPathToBackup());
            /*
             * optionally store backup info with save modules
             */
            $saveModule->saveBackupInfo($this->BackupInfo, $this->getBackupInfoFilename());
            $saveModule->cleanup();
        }
        $this->postHook();
        $this->end();
    }

    /**
     * @param $dest
     * @param $contents
     * @param int $mode
     */
    public function writeFile($dest, $contents, $mode = 0644)
    {
        $this->connection->writeFileContents($dest, $contents, $mode);
    }

    /**
     * @return Info
     */
    public function fetchBackupInfo()
    {
        $remote_file = $this->getBaseBackupDirectory() . DIRECTORY_SEPARATOR .
            $this->getBackupInfoFilename();
        if ($this->getConnection()->file_exists($remote_file)) {
            $file_contents = $this->getConnection()->getFileContents($remote_file);
            $this->logInfo($file_contents);
            $this->BackupInfo = unserialize($file_contents);
            $this->logInfo($this->BackupInfo);
        } else {
            $this->BackupInfo = new Info();
        }
        return $this->BackupInfo;
    }

    /**
     *
     */
    protected function postHook()
    {

    }

    /**
     * Sets backup directory using SaveDirectoryPrefix and start date
     */
    protected function setRelativeBackupDirectory()
    {
        $this->relative_backup_directory = $this->getSaveDirectoryPrefix() .
            date("m-j-Y--H-i-s", $this->getStartDate());
    }

    /**
     * @return Info
     */
    public function getBackupInfo()
    {
        return $this->BackupInfo;
    }

    /**
     * @param Info $BackupInfo
     */
    public function setBackupInfo(Info $BackupInfo)
    {
        $this->BackupInfo = $BackupInfo;
    }

    /**
     * @return void
     */
    abstract public function saveBackupInfo();

    /**
     * @return void
     */
    abstract public function performBackup();
}
