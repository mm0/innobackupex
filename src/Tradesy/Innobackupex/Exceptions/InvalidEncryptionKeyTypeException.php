<?php

namespace Tradesy\Innobackupex\Exceptions;

/**
 * Class InvalidEncryptionKeyTypeException
 * @package Tradesy\Innobackupex\Exceptions
 */
class InvalidEncryptionKeyTypeException extends \Exception
{
    /**
     * InvalidEncryptionKeyTypeException constructor.
     * @param string $message
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct($message, $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }


}