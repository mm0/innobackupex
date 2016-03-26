<?php

namespace Tradesy\Innobackupex;

interface LoadInterface
{

    public function load(\Tradesy\Innobackupex\Backup\Info $backupInfo);

    public function testSave();

    public function cleanup();

    public function verify();

    public function getBackupInfo();
    /**
     * @param mixed $key
     */
    public function setKey($key);

}