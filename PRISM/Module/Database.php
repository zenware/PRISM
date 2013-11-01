<?php

namespace PRISM\Module;

/**
 * PHPInSimMod - Database Module
 * 
 * I'm not exactly sure the purpose of this, can't you get by on just PDO?
 * 
 * @package PRISM
 * @subpackage Database
*/
class Database
{
    // Hold an instance of the class
    private $_instance;
    // A private constructor; prevents direct creation of object
    public function __construct() {}
    // The singleton method
    public function init($dsn, $startUpStr, $user = null, $pass = null, $port = null)
    {
        if (!isset($this->instance)) {
            $this->_instance = new PDO($dsn, $startUpStr, $user, $pass, $port);
        }

        return $this;
    }

    // Prevent users to clone the instance
    public function __clone()
    {
        trigger_error('Clone is not allowed.', E_USER_WARNING);
    }
}
