<?php

namespace Connections;

/**
 * Class LocalShellConnectionTest
 */
class LocalShellConnectionTest extends \AbstractConnectionTest
{
    /**
     *
     */
    public function setUp()
    {
    }

    /**
     *
     */
    public function tearDown()
    {
        $this->connection = null;
    }

    /**
     *
     */
    public function createConnection()
    {
        $this->connection = new \Tradesy\Innobackupex\LocalShell\Connection();
        $this->connection->setSudoAll(true);
    }

}