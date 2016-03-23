<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 3/22/16
 * Time: 6:19 PM
 */
namespace Tradesy\Innobackupex\Backup;

class Info {
$BackupInfo = array(
"latest_full_backup" => $this->getActualDirectory(),
"latest_full_backup_s3_directory" => date("m-j-Y--H-i-s", $this->getStartDate()) . "/",
"latest_full_backup_s3_bucket" => $this->getS3Bucket(),
"latest_full_backup_s3_full_path" => $this->getS3Bucket() . $this->getS3Name() . ".tar.gz",
"latest_full_backup_create_datetime" => date("m-j-Y--H-i-s", $this->getStartDate()),
"latest_full_backup_local_path" => $this->getFullPathToBackup(),
"latest_incremental_backup" => array(),
"incremental_backup_list" => array()
);

}