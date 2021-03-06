<?php

namespace Tradesy\Innobackupex;

/**
 * Class ConnectionResponse
 * @package Tradesy\Innobackupex
 */
class ConnectionResponse
{
    use LoggingTraits;

    /**
     * @var string
     */
    protected $stdout;
    /**
     * @var string
     */
    protected $stderr;
    /**
     * @var string
     */
    protected $command;


    public function __construct(
        $command,
        $stdout,
        $stderr = null
    ) {
        $this->command = $command;
        $this->stdout = $stdout;
        $this->stderr = $stderr;
    }

    /**
     * @return string  The host.
     */
    public function command()
    {
        return $this->command;
    }

    /**
     * @return int  The host.
     */
    public function stdout()
    {
        return $this->stdout;
    }

    /**
     * @return string  The username.
     */
    public function stderr()
    {
        return $this->stderr;
    }

    public function showOutput()
    {
        $this->logInfo($this->stdout());
        $this->logError($this->stderr());
    }

    public function __toString()
    {
        return $this->stdout . "\n" . $this->stderr;
    }
}