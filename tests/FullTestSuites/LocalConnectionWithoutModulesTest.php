<?php

namespace FullTestSuites;
/**
 * Class LocalConnectionWithoutModulesTest
 */
class LocalConnectionWithoutModulesTest extends \AbstractFullBackupThenRestoreTest
{
    /**
     *
     */
    public function createConnection()
    {
        $this->connection = new \Tradesy\Innobackupex\LocalShell\Connection();
        $this->connection->setSudoAll(true);
    }

    public function setupSaveModules()
    {
        $this->save_modules = array();
    }
    public function setupRestoreModules()
    {
        $this->restore_modules = array();
    }

}