<?php

namespace Tradesy\Innobackupex;

class LogEntry
{
    /**
     * Echoes the passed string to console
     *
     * @param $entry
     */
    public static function logEntry($entry) {
        echo date('Y-m-d H:i:s') . ' ' . $entry . "\n";
    }

}
