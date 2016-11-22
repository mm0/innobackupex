<?php

namespace Tradesy\Innobackupex;

use Tradesy\Innobackupex\Backup\Info;

interface LoadInterface
{

    public function load(Info $backupInfo, $backup);

    public function testSave();

    public function cleanup();

    public function verify();


    /**
     * @return Info
     */
    public function getBackupInfo($file);
    /**
     * @param mixed $key
     */
    public function setKey($key);

}