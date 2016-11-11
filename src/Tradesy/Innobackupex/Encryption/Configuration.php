<?php
/**
 * Created by Matt Margolin
 * Date: 3/23/16
 * Time: 2:04 PM
 * Reference: https://www.percona.com/doc/percona-xtrabackup/2.2/innobackupex/encrypted_backups_innobackupex.html
 */

namespace Tradesy\Innobackupex\Encryption;

use Tradesy\Innobackupex\Exceptions\EncryptionAlgorithmNotSupportedException;
use Tradesy\Innobackupex\Exceptions\InvalidEncryptionKeyTypeException;

/**
 * Class Configuration
 * @package Tradesy\Innobackupex\Encryption
 */
class Configuration
{
    /**
     * @var string
     */
    protected $algorithm;
    
    /**
     * @var string
     */
    protected $encryption_key;
    
    /*
     * TODO: Encryption key file support
     */
    
    /**
     * @var string
     */
    protected $encryption_key_file;

    /**
     * @var array
     */
    protected static $supported_algorithms = array(
        "AES128",
        "AES192",
        "AES256"
);
    /**
     * Configuration constructor.
     * @param string $algorithm
     * @param string $key
     */
    public function __construct(
         $algorithm,
         $key
    ) {
        if(!in_array($algorithm,self::$supported_algorithms)){
            throw new EncryptionAlgorithmNotSupportedException(
                "Algorithm must be one of: " . join(self::$supported_algorithms, ",\n ") . "\n" .
                $algorithm . " was specified ",
                0
            );
        }
        $this->algorithm = $algorithm;
        if(!is_string($key) || !strlen($key)){
            throw new InvalidEncryptionKeyTypeException(
                "Error: Encryption key must be of type String and length > 0, \n" .
                "Type Provided: " . gettype($key),
                0
            );
        }
        $this->encryption_key = $key;
    }

    /**
     * @return string
     */
    public function getConfigurationString(){
        return " --encrypt=" . $this->algorithm . 
                " --encrypt-key=" . $this->encryption_key;
    }
    /**
     * @return string
     */
    public function getDecryptConfigurationString(){
        return " --decrypt=" . $this->algorithm .
        " --encrypt-key=" . $this->encryption_key;
    }
}