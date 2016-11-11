# Innobackupex
---

Innobackupex is a perl utility created by Percona for creating and restoring filesystem backups for MySQL

This is a PHP Wrapper for Innobackupex that allows us to use Object-Oriented design to Backup/Restore MySQL servers and automates the archival of backups onto cloud storage solutions such as AWS S3 and Google Cloud Storage.

This package is created as a PSR-0 namespaced library that is installable via PHP Composer.  

Very little configuration is required to use this library.

Requirements
---

- PHP 5+
- PHP Composer
- (Optional) AWS CLI
- (Optional) GCS CLI
- MySQL Server with enough free disk space to create a backup
- (Optional) SSH Access to the MySQL Server
- (Required for SSH) libssh2-php
- php5-curl (required for guzzle)
- python-mysqldb # necessary for ansible mysql module

Methodology
---
This library is useable in several different ways.  The simplest method requires PHP and this library to be downloaded onto the MySQL Server itself.  

Alternatively, we are able to use the PHP_SSH2 Library in order to run this library remotely against any server with mysql and ssh access. 


Installation
---
Include this repository within your composer.json package of your library

Run `composer install`


Configuration
---


### MySQL Configuration
```
<?php

require_once __DIR__."/../../vendor/autoload.php";

$mysql_host = "127.0.0.1";   /* this should be localhost (IP since not using unix socket) because we are connecting via ssh below */
$mysql_user = "root";
$mysql_password = "password";
$mysql_port = 3306;


// Create MySQL configuration object
$mysql_config = new \Tradesy\Innobackupex\MySQL\Configuration (
    $mysql_host,
    $mysql_user,
    $mysql_password,
    $mysql_port
);
```
### Enabling Optional Encryption of Archives via Encryption Configuration

```
$algorithm = "AES256";         /* Currently Supported:        "AES128", "AES192", "AES256" */
$key = "MY_STRING_ENCRYPTION_KEY"; 


// Create Encryption Configuration Object
$encryption_configuration = new \Tradesy\Innobackupex\Encryption\Configuration(
    $algorithm,
    $key 
);
```

### SSH Connection Configuration 
```
$ssh_host = "127.0.0.1"; // or DNS name 
$ssh_port = 22; 
$ssh_user = "vagrant";
$ssh_public_key = "/path/to/public_key";
$ssh_private_key = "/path/to/private_key";

$ssh_config = new \Tradesy\Innobackupex\SSH\Configuration (
    $ssh_host,
    $ssh_port,
    $ssh_user,
    $ssh_public_key,
    $ssh_private_key,
    '',             // ssh key passphrase
    array('hostkey' => 'ssh-rsa')
);
```

We then use this configuration object to create a connection object:

```
$connection = new \Tradesy\Innobackupex\SSH\Connection($ssh_config);
$connection->setSudoAll(true); // set this to true if you are using a non-root user to SSH 

```

Alternatively, instead of SSH'ing into your MySQL Box, you can also run this library directly on your server using a LocalShell/Connection Object

### LocalShell Connection Configuration
```
$connection = new \Tradesy\Innobackupex\LocalShell\Connection()
$connection->setSudoAll(true); // set this to true if you are using a non-root user
```

### Save Modules
Save Modules determine where we will store our backups.  You can specify one or more save modules in an array

To date, we have only implemented AWS S3 and GCS Save Modules.  The goal is to have additional modules implemented using the same interfaces for us to be able to archive onto more providers by simply adding the configuration to the save module array


#### AWS S3 Save Module
There are two ways to use the AWS S3 Save Module.  We can either use AWS's PHP SDK to handle the upload for us `(\Tradesy\Innobackupex\S3\Local\Upload)`, or if this is not desired, we can fallback to using the shell AWS cli `(\Tradesy\Innobackupex\S3\Remote\Upload)` which has it's own independent credentials and configuration  

In order to use the local module, we must be using a LocalShell Connection as this library and composer dependency (AWS-PHP-SDK) is required.

With an SSH Connection, we are limited in that we don't have a copy of this library in th
```
// Specify the storage module for the backup to use (local or remote SSH)

$s3_save_module = new \Tradesy\Innobackupex\S3\Remote\Upload(
    $connection,
    $bucket, 
    $region,
    $concurrency
);
```

#### Google Cloud Storage Save Module
At the moment, GCS PHP SDK is not capable of handling large file uploads without exhausting memory resources.  Because of this, using the `\Tradesy\Innobackupex\GCS\Remote\Upload` module is suggested.
```
$google_save_module = new \Tradesy\Innobackupex\GCS\Remote\Upload(
    $connection,
    $bucket,
    $region,
    $concurrency
);
```



Now that you have a  mysql configuration, connection, and save module objects, you are ready to create the Backup Object:

#### Full Backup Object

```
 $Backup = new \Tradesy\Innobackupex\Backup\Full(
     $mysql_config,
     $connection,
     [$s3_save_module, $google_save_module],     // Array of save modules, minimum one
     $encryption_configuration,                  // Encryption configuration or null
     $compress = true,                           // Specify whether to compress backup
     $compress_threads = 100,                    // Specify # threads for compression
     $parallel_threads = 100,                            // Specify # threads
     $encryption_threads = 100,                  // Specify # threads for encryption
     $memory = "4G",                             // Specify RAM Usage
     $save_directory = "/tmp/backups",           // Specify the directory used to save backup
     $save_directory_prefix = "full_backup_"     // Specify prefix for the full backup name
 );
 
```
to run the backup script, simply call: 

```
$Backup->Backup();
```

This stores a serialized PHP Object of type `\Tradesy\Innobackupex\Backup\Info` into the `$save_directory` with information about relevant incremental and full backups for later use by the restoration process or additional incremental backups

#### Incremental Backup
 
 Create the `Incremental` object
 
 ```
 $Backup = new \Tradesy\Innobackupex\Backup\Incremental(
     $mysql_config,
     $connection,
     [$s3_save_module, $google_save_module],              // Array of save modules, minimum one
     $encryption_configuration,                          // Encryption configuration or null
     $compress = true,                                   // Specify whether to compress backup
     $compress_threads = 100,                            // Specify # threads for compression
     $parallel_threads = 100,                            // Specify # threads
     $encryption_threads = 100,                          // Specify # threads for encryption
     $memory = "4G",                                     // Specify RAM Usage
     $save_directory = "/tmp/backups",                   // Specify the directory used to save backup
     $save_directory_prefix = "incremental_backup_"      // Specify prefix for to call the full backup
 );
 ```
Load the previous Backup Info serialized file 

```
 /*
  *   First get files present on backup server
  */
 $info = $Backup->fetchBackupInfo();
 

```
 
#### Create the Backup
```
 /*
  *   Create the backup
  */
 $Backup->Backup();
```
 
 
## Restoring from Backups
 Please be sure to only run this when necessary and likely, on a non-production server as it will erase all existing MySQL Data.
 
 The restoration process looks something like this:
 
 - Download the Backup Info serialized object/file.  This is typically stored within the same dated backup directory in your bucket.  This holds information about where to find archives and naming conventions
 - For each desired set of a single Full Backup and related Incremental Backups:
 - Download archive to local system
 - Extract and optionally decrypt the archive
 
 All of this is automated for your via our Restoration Configuration, Connection and Restore Modules:
 
 ### ***You must manually remove /var/lib/mysql on your target server in order to commence a Restoration.
 
Load Backup Info Object from disk 

```
 $BackupInfo = unserialize($connection->getFileContents("/tmp/backups/tradesy_percona_backup_info"));
```

Use the same $mysql_config object as before.

Use localshell connection for fastest restoration:

```
$connection = new \Tradesy\Innobackupex\LocalShell\Connection();
$connection->setSudoAll(true); 
```

Create desired restore modules 

```
$aws_restore_module = new \Tradesy\Innobackupex\S3\Local\Download(
    $connection,
    $bucket,
    $region,
    $concurrency
);
```

Create the Restore Object

```
  $Restore = new \Tradesy\Innobackupex\Restore\Mysql(
      $mysql_config,
      $connection,
      [$aws_restore_module],
      $encryption_configuration,                          // Encryption configuration or null
      $parallel_threads = 100,                            // Parallel threads
      $memory = "10G"                                     // Specify RAM Usage
  );
  $Restore->setBackupInfo($BackupInfo);
```

When ready, simply call the restore method.  All archives will be downloaded via the restore modules and prepared automatically.  Additionally, the method will restore the database.
 
```
 $Restore->runRestore();
```
 
#### Sample Crontab
```
# MySQL Backups
# daily full backup at 12:15 am
15 0 * * * /usr/bin/php /var/cli/mysql_backups/CreateFullBackup.php >> /var/log/cron/mysql_backups.log 2>&1
 
# hourly incremental backups (except 24th backup)
15 1-23 * * * /usr/bin/php /var/cli/mysql_backups/CreateIncrementalBackup.php >> /var/log/cron/mysql_backups.log 2>&1
```
 
#### For more examples, please see the `./Examples` directory
 
 
### Testing


Start Vagrant:

```vagrant up```


Log into VM:

`vagrant ssh`


CD to shared Directory:

`cd /var/www`


Install composer packages:

`composer install --dev`


Run PHPUnit

`./vendor/bin/phpunit -v --debug`


To Reset the Database after failed test suite:

Inside vm: 

`sudo rm -rf /var/lib/mysql`

On Host: 

```vagrant provision --provision-with reset_mysql```
 
## License

Copyright (c) 2016, Matt Margolin

All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

3. Neither the name of the copyright holder nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

Author Information
------------------

[Matt Margolin](mailto:matt.margolin@gmail.com)

[mm0](github.com/mm0) on github
