<?php

namespace Tradesy\Innobackupex;

interface ConnectionInterface
{

    function executeCommand($command);

    function getFileContents($file);

    function writeFileContents($file, $contents, $mode = 0644);

    function getConnection();

    function verify();

}