<?php

namespace Tradesy\Innobackupex;

use Tradesy\Innobackupex\Backup\Info;

/**
 * Interface SaveInterface
 * @package Tradesy\Innobackupex
 */
interface SaveInterface
{

    /**
     * @param string $filename
     * @return mixed
     */
    public function save($filename);
    /**
     * @param string $filename
     * @return mixed
     */
    public function saveFile($filename);
    /**
     * @param string $filename
     * @return mixed
     */
    public function saveDirectory($filename);

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
    public function saveBackupInfo(Info $backupInfo, $filename);
    /**
     * @param mixed $key
     */
    public function setKey($key);

}