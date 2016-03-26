<?php

namespace Tradesy\Innobackupex;

interface LoadInterface
{

    public function load(\Tradesy\Innobackupex\Backup\Info $backupInfo);

    public function testSave();

    public function cleanup();

    public function verify();


    /**
     * @return \Tradesy\Innobackupex\Backup\Info
     */
    public function getBackupInfo($file);
    /**
     * @param mixed $key
     */
    public function setKey($key);

}