<?php

namespace Tradesy\Innobackupex\Backup;
use \Tradesy\Innobackupex\Backup\Abstract;

class Incremental extends Abstract{

    protected $base_dir;

    /**
     * @return mixed
     */
    public function getBaseDir()
    {
        return $this->base_dir;
    }

    /**
     * @param mixed $base_dir
     */
    public function setBaseDir($base_dir)
    {
        $this->base_dir = $base_dir;
    }

    public function setSaveName(){
        $this->save_name = "incremental_backup_" . date("m-j-Y--H-i-s", $this->getStartDate());
    }

    public function __construct($connection){
        $this->setType("INCREMENTAL");
        parent::__construct($connection);
    }
    public function setS3Name(){
        $this->s3_name = $this->BackupInfo['latest_full_backup_s3_directory'] . $this->getSaveName();
    }


    public function PerformBackup(){
        $command = "sudo innobackupex --no-timestamp --incremental " . $this->getActualDirectory() . " --incremental-basedir=". $this->getBaseDir();
        echo "Backup Command: $command \n";
        $stream = ssh2_exec($this->SSH_Connection,$command );
        $stderrStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
        stream_set_blocking($stderrStream, true);
        stream_set_blocking($stream, true);
        $stdout = stream_get_contents($stream);
        $stderr = stream_get_contents($stderrStream);

        echo $stdout . "\n";
        echo $stderr . "\n";
        fclose($stream);
    }

    public function SaveBackupInfo()
    {
        $path = $this->getActualDirectory();
        // get old inc backup
        $inc_base_dir = $this->BackupInfo['latest_incremental_backup'];

        $this->BackupInfo['latest_incremental_backup'] = array( "s3_directory" => $this->BackupInfo['latest_full_backup_s3_directory'],
                                                                "s3_bucket" => $this->getS3Bucket(),
                                                                "s3_full_path" => $this->getS3Bucket() . $this->getS3Name() . ".tar.gz",
                                                                "create_datetime" => date("m-j-Y--H-i-s", $this->getStartDate()),
                                                                "local_path" => $this->getFullPathToBackup(),
                                                                "actual_directory" => $path );
        if(count($inc_base_dir)){
            array_push($this->BackupInfo['incremental_backup_list'],$inc_base_dir);
        }

        file_put_contents('/tmp/tradesy_percona_backup_info',json_encode($this->BackupInfo));
        $this->uploadFileToServer("/tmp/tradesy_percona_backup_info","/home/".$this->SSH_User ."/tradesy_percona_backup_info",0644);
	$command = "sudo s3cmd put /tmp/tradesy_percona_backup_info" . " " . $this->getS3Bucket() . "tradesy_percona_backup_info";
	echo "Upload latest backup info to S3 with command: $command \n";
	$commandOutput = exec($command);
	echo $commandOutput;

        # Write to hourly backup file
        $command = "sudo echo '". $path . "' > ~/latest_percona_mysql_hourly_backup";
        $this->SSH_Command($command,true,false);
    }

    public function setBaseDirInfo(){
        $daily_base_dir = $this->BackupInfo['latest_full_backup'];
        $inc_base_dir = $this->BackupInfo['latest_incremental_backup'];
        if(count($inc_base_dir)){
            $this->setBaseDir($inc_base_dir['actual_directory']);
        }else {
            if(strlen($daily_base_dir)>5){
                $this->setBaseDir($daily_base_dir);
            }else{
                die("Error! Base Directory not found for incremental Backup");
            }
        }
    }

}
