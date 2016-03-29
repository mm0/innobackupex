<?php

namespace Tradesy\Innobackupex;

/**
 * Interface SaveInterface
 * @package Tradesy\Innobackupex
 */
interface SaveInterface
{

    /**
     * @param $filename
     * @return mixed
     */
    public function save($filename);

    /**
     * @return mixed
     */
    public function testSave();

    /**
     * @return mixed
     */
    public function cleanup();

    /**
     * @return mixed
     */
    public function verify();

    /**
     * @param Backup\Info $backupInfo
     * @param $filename
     * @return mixed
     */
    public function saveBackupInfo(\Tradesy\Innobackupex\Backup\Info $backupInfo, $filename);
    /**
     * @param mixed $key
     */
    public function setKey($key);

}