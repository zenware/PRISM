<?php
/**
 * bootstrap.php - Launches the PRISM Applictaion.
 *
 * @category Application
 * @package  PRISM
 * @author   zenware (Jay Looney) <jay.m.looney@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT License
 */

require_once 'PRISM.php';

/* Defines */
// PRISM

define('PRISM_DEBUG_CORE',    1);        // Shows Debug Messages From the Core
define('PRISM_DEBUG_SOCKETS', 2);        // Shows Debug Messages From the Sockets Module
define('PRISM_DEBUG_MODULES', 4);        // Shows Debug Messages From the all Modules
define('PRISM_DEBUG_PLUGINS', 8);        // Shows Debug Messages From the Plugins
define('PRISM_DEBUG_ALL',    15);        // Shows Debug Messages From All

define('MAINTENANCE_INTERVAL', 2);       // The frequency in seconds to do connection maintenance checks.

// Return Codes:
define('PLUGIN_CONTINUE', 0);            // Plugin passes through operation. Whatever called it continues.
define('PLUGIN_HANDLED',  1);            // Plugin halts continued operation. Plugins following in the plugins.ini won't be called.
define('PLUGIN_STOP',     2);            // Plugin stops timer from triggering again in the future.

define('RAND_ASCII', 1);
define('RAND_ALPHA', 2);
define('RAND_NUMERIC', 4);
define('RAND_HEX', 8);
define('RAND_BINARY', 16);

error_reporting(E_ALL);
ini_set('display_errors',		'true');

define('ROOTPATH', dirname(realpath(__FILE__)));

$PRISM = new PRISM\PRISM();
$PRISM->init($argc, $argv);
$PRISM->start();
