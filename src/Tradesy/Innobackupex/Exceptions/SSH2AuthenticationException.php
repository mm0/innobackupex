<?php

namespace Tradesy\Innobackupex\Exceptions;

/**
 * Class SSH2AuthenticationException
 * @package Tradesy\Innobackupex\Exceptions
 */
class SSH2AuthenticationException extends \Exception
{
    /**
     * SSH2AuthenticationException constructor.
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