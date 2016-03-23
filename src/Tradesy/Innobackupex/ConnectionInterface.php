<?php

namespace Tradesy\Innobackupex;

interface ConnectionInterface
{
    /**
     * @return ConnectionResponse
     */
    function executeCommand($command);

    function getFileContents($file);

    function writeFileContents($file, $contents, $mode = 0644);
    /**
     * @return resource
     */
    function getConnection();

    function verify();

}