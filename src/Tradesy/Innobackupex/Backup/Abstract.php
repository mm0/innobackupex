<?php

namespace Tradesy\Innobackupex\Backup;

use Tradesy\Innobackupex\MySQL\Configuration;
use Tradesy\Innobackupex\ConnectionInterface;
use Tradesy\Innobackupex\Exceptions\InnobackupexException;
use Tradesy\Innobackupex\SaveInterface;

abstract class Abstract
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
     * @var \Tradesy\Innobackupex\SaveInterface
     */
    protected $save_module;
    protected $date;
    protected $start_date;
    protected $end_date;
    protected $memory_limit;
    protected $actual_directory;
    protected $backup_info_filename = "tradesy_percona_backup_info";
    protected $BackupInfo;

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
     * @return mixed
     */
    public function getCompress()
    {
        return $this->compress;
    }

    /**
     * @param mixed $compress
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
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getConnection()
    {
        return $this->connection;
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


    public function __construct(
        \Tradesy\Innobackupex\MySQL\Configuration $mysql_configuration,
        \Tradesy\Innobackupex\ConnectionInterface $connection,
        \Tradesy\Innobackupex\SaveInterface $save_module,
        $compress                       = false,
        $memory                         = "1G",
        $save_directory                 = "tmp",
        $save_directory_prefix          = "full_backup"
    ) {
        $this->mysql_configuration      = $mysql_configuration;
        $this->connection               = $connection;
        $this->save_module              = $save_module;
        $this->compress                 = $compress;
        $this->memory                   = $memory;
        $this->save_directory           = $save_directory;
        $this->save_directory_prefix    = $save_directory_prefix;

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
            $this->test_s3cmd();
        }
        $this->start();
        $this->PerformBackup();
        //  $this->ApplyLog();
        echo "Saved to " . $this->actual_directory . "\n";
        $this->Compress();
        $this->save_module->save($this->getSaveName());
        $this->save_module->cleanup();

        $this->PostHook();
        $this->end();
    }

    public function writeFile($dest, $contents, $mode = 0644)
    {
        $this->connection->writeFileContents($dest, $contents, $mode);
    }

    public function fetchBackupInfo()
    {
        $remote = "/home/" . $this->SSH_User . "/" . $this->getBackupInfoFilename();
        $file_contents = $this->connection->getFileContents($remote);
        $this->BackupInfo = json_decode($file_contents, true);

        return $this->BackupInfo;
    }

    private function UploadToS3()
    {
        if ($this->getCompress()) {
            # upload compressed file to s3
            $command = "sudo s3cmd put " . $this->getFullPathToBackup() . " " . $this->getS3Bucket() . $this->getS3Name() . ".tar.gz";
            $stream = ssh2_exec($this->SSH_Connection, $command);
            $stderrStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
            stream_set_blocking($stream, true);
            stream_set_blocking($stderrStream, true);
            $stdout = stream_get_contents($stream);
            $stderr = stream_get_contents($stderrStream);

            echo $stdout . "\n";
            echo $stderr . "\n";
            fclose($stream);
            fclose($stderrStream);
        }
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

    private function Compress()
    {
        if ($this->getCompress()) {
            switch ($this->compressionType) {
                case "gzip":
                    break;

                case "tar":
                    $response = $this->connection->executeCommand(
                        "sudo tar -C " .
                        $this->getSaveDirectory() .
                        " -zcvf  " .
                        $save_file . " " .
                        $this->getSaveName()
                    );
                    break;
                case "innobackupex":
                    ;
                default:
                    // build in compression, this function should do nothing
                    ;
            }
        } else {
            echo "Skipping Compression \n";
        }
    }


    abstract function SaveBackupInfo();

    abstract function PerformBackup();
}
