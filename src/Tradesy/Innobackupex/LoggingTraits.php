<?php
/**
 * Created by Matt Margolin.
 * Date: 3/28/16
 * Time: 1:29 PM
 */
namespace Tradesy\Innobackupex;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Class LoggingTraits
 * @package Tradesy\Innobackupex
 */
trait LoggingTraits
{
    /**
     * @param $message
     */
    public function logTrace($message)
    {
        $this->log($message, "trace");
    }

    /**
     * @param $message
     */
    public function logDebug($message)
    {
        $this->log($message, "debug");
    }

    /**
     * @param $message
     */
    public function logError($message)
    {
        $this->log($message, "error");
    }

    /**
     * @param $message
     * @param string $severity
     */
    public function log($message, $severity = "trace")
    {

    }
}