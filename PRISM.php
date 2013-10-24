<?php
/* PHPInSimMod
*
* by the PHPInSimMod Development Team.
*
*/

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

// the REQUIRED modules for PRISM.
/*
require_once(ROOTPATH . '/modules/prism_functions.php');
require_once(ROOTPATH . '/modules/prism_config.php');
require_once(ROOTPATH . '/modules/prism_packets.php');
require_once(ROOTPATH . '/modules/prism_hosts.php');
require_once(ROOTPATH . '/modules/prism_statehandler.php');
require_once(ROOTPATH . '/modules/prism_http.php');
require_once(ROOTPATH . '/modules/prism_telnet.php');
require_once(ROOTPATH . '/modules/prism_admins.php');
require_once(ROOTPATH . '/modules/prism_timers.php');
require_once(ROOTPATH . '/modules/prism_plugins.php');
*/


$PRISM = new PRISM();
$PRISM->initialise($argc, $argv);
$PRISM->start();

/**
 * PHPInSimMod
 * @package PRISM
 * @author Dygear (Mark Tomlin) <Dygear@gmail.com>
 * @author ripnet (Tom Young) <ripnet@gmail.com>
 * @author morpha (Constantin Köpplinger) <morpha@xigmo.net>
 * @author Victor (Victor van Vlaardingen) <vic@lfs.net>
 * @author GeForz (Kai Lochbaum)
*/
class PRISM
{
    const VERSION = '0.4.4';
    const ROOTPATH = ROOTPATH;

    /* Run Time Arrays */
    public $config                = null;
    public $hosts                = null;
    public $http                = null;
    public $telnet                = null;
    public $plugins                = null;
    public $admins                = null;

    // Time outs
    private $_sleep                = null;
    private $_uSleep                = null;

    private $_nextMaintenance    = 0;
    public $isWindows            = false;

    // Main while loop will run as long as this is set to true.
    private $_isRunning            = false;

    // Real Magic Functions
    public function __construct()
    {
        // This reregisters our autoload magic function into the class.
        spl_autoload_register(__CLASS__ . '::_autoload');
        set_error_handler(__CLASS__ . '::_errorHandler', E_ALL | E_STRICT);

        // Windows OS check
        if (preg_match('/^win/i', PHP_OS)) {
            $this->isWindows = true;
        }

        // there are functional paradigms that allow awesomeness.
        $this->config    = new ConfigHandler();
        $this->hosts    = new HostHandler();
        $this->plugins    = new PluginHandler();
        $this->http        = new HttpHandler();
        $this->telnet    = new TelnetHandler();
        $this->admins    = new AdminHandler();
    }

    // Pseudo Magic Functions
    private static function _autoload($className)
    {
        include_once ROOTPATH . "/modules/prism_" . strtolower($className) . ".php";
    }

    public static function _errorHandler($errno, $errstr, $errfile, $errline, $errcontext)
    {
        // This error code is not included in error_reporting
        if (!(error_reporting() & $errno)) {
            return;
        }

        switch ($errno) {
            case E_ERROR:
            case E_USER_ERROR:
                    echo 'PHP ERROR:'.PHP_EOL;
                    $andExit = true;
                break;
            case E_WARNING:
            case E_USER_WARNING:
                    echo 'PHP WARNING:'.PHP_EOL;
                break;
            case E_NOTICE:
            case E_USER_NOTICE:
                    echo 'PHP NOTICE:'.PHP_EOL;
                break;
            case E_STRICT:
                    echo 'PHP STRICT:'.PHP_EOL;
                break;
            default:
                    echo 'UNKNOWN:'.PHP_EOL;
                break;
        }

        echo "\t$errstr in $errfile on line $errline".PHP_EOL; // Side Effect :O

        $trace = debug_backtrace();
        foreach ($trace as $index => $call) {
            if ($call['function'] == 'main') break;
            if ($index > 0 AND isset($call['file']) AND isset($call['line'])) {
                PRISM::console("\t".$index.' :: '.$call['function'].' in '.$call['file'].':'.$call['line']);
            }
        }

        if (isset($andExit) AND $andExit == true)
            exit(1);

        // Don't execute PHP internal error handler
        // Why not?

        return true;
    }

    public function init($argc, $argv)
    {
        // Set the timezone
        if (isset($this->config->cvars['defaultTimeZone']))
            date_default_timezone_set($this->config->cvars['defaultTimeZone']);
        else {
            // I know, I'm using error suppression, but I swear it's appropriate!
            // Why?
            $timeZoneGuess = @date_default_timezone_get();
            date_default_timezone_set($timeZoneGuess);
            unset($timeZoneGuess);
        }

        // Initialise handlers (load config files)
        // This is a bloody mess.
        if (!$this->config->init() OR !$this->hosts->init()
            OR !$this->http->init() OR !$this->telnet->init()
            OR !$this->admins->init() OR !$this->plugins->init()
        ) {
            PRISM::console'Fatal error encountered. Exiting...');
            exit(1);
        }

        $pluginsLoaded = $this->plugins->loadPlugins();

        if ($this->config->cvars['debugMode'] & PRISM_DEBUG_CORE) {
            if ($pluginsLoaded == 0)
                PRISM::console'No Plugins Loaded');
            else if ($pluginsLoaded == 1)
                PRISM::console'One Plugin Loaded');
            else
                PRISM::console"{$pluginsLoaded} Plugins Loaded.");
        }
    }

    public function start()
    {
        if ($this->_isRunning)
            return;

        $this->_isRunning = true;
        $this->_nextMaintenance = time () + MAINTENANCE_INTERVAL;

        $this->_main();
    }

    private function _main()
    {
        while ($this->_isRunning === true) {
            // Setup our listen arrays
            $sockReads = $sockWrites = $socketExcept = array();

            if (!$this->isWindows) {
                $sockReads[] = STDIN; // What exactly about this matters?
            }

            // Add host sockets to the arrays as needed
            // While at it, check if we need to connect to any of the hosts.
            $this->hosts->getSelectableSockets($sockReads, $sockWrites);

            // Add http sockets to the arrays as needed
            $this->http->getSelectableSockets($sockReads, $sockWrites);

            // Add telnet sockets to the arrays as needed
            $this->telnet->getSelectableSockets($sockReads, $sockWrites);

            // Update timeout if there are timers waiting to be fired.
            $this->updateSelectTimeOut($this->sleep, $this->uSleep);

            // Error suppression used because this function returns a "Invalid CRT parameters detected" only on Windows.
            // Can we find a better function?
            $numReady = @stream_select($sockReads, $sockWrites, $socketExcept, $this->sleep, $this->uSleep);

            // Keep looping until you've handled all activities on the sockets.
            while ($numReady > 0) {
                $numReady -= $this->hosts->checkTraffic($sockReads, $sockWrites);
                $numReady -= $this->http->checkTraffic($sockReads, $sockWrites);
                $numReady -= $this->telnet->checkTraffic($sockReads, $sockWrites);

                // KB input
                if (in_array(STDIN, $sockReads)) {
                    $numReady--;
                    $kbInput = trim(fread(STDIN, STREAM_READ_BYTES));

                    // Split up the input
                    $exp = explode(' ', $kbInput);

                    // Process the command (the first char or word of the line)
                    switch ($exp[0]) {
                    case 'c':
                        PRISM::console(sprintf('%32s - %64s', 'COMMAND', 'DESCRIPTOIN'));
                        foreach ($this->plugins->getPlugins() as $plugin => $details) {
                            foreach ($details->sayCommands as $command => $detail) {
                                PRISM::console(sprintf('%32s - %64s', $command, $detail['info']));
                            }
                        }

                        break;
                    case 'h':
                        PRISM::console(sprintf('%14s %28s:%-5s %8s %22s', 'Host ID', 'IP', 'PORT', 'UDPPORT', 'STATUS'));
                        foreach ($this->hosts->getHostsInfo() as $host) {
                            $status = (($host['connStatus'] == CONN_CONNECTED) ? '' : (($host['connStatus'] == CONN_VERIFIED) ? 'VERIFIED &' : ' NOT')).' CONNECTED';
                            $socketType = (($host['socketType'] == SOCKTYPE_TCP) ? 'tcp://' : 'udp://');
                            PRISM::console(sprintf('%14s %28s:%-5s %8s %22s', $host['id'], $socketType.$host['ip'], $host['port'], $host['udpPort'], $status));
                        }
                        break;

                    case 'I':
                        PRISM::console'RE-INITIALISING PRISM...');
                        $this->initialise(null, null);
                        break;

                    case 'p':
                        PRISM::console(sprintf('%28s %8s %24s %64s', 'NAME', 'VERSION', 'AUTHOR', 'DESCRIPTION'));
                        foreach ($this->plugins->getPlugins() as $plugin => $details) {
                            PRISM::console(sprintf("%28s %8s %24s %64s", $plugin::NAME, $plugin::VERSION, $plugin::AUTHOR, $plugin::DESCRIPTION));
                        }
                        break;

                    case 'x':
                        $this->isRunning = false;
                        break;

                    case 'w':
                        PRISM::console(sprintf('%15s:%5s %5s', 'IP', 'PORT', 'LAST ACTIVITY'));
                        foreach ($this->http->getHttpInfo() as $v) {
                            $lastAct = time() - $v['lastActivity'];
                            PRISM::console(sprintf('%15s:%5s %13d', $v['ip'], $v['port'], $lastAct));
                        }
                        PRISM::console'Counted '.$this->http->getHttpNumClients().' http client'.(($this->http->getHttpNumClients() == 1) ? '' : 's'));
                        break;

                    default :
                        PRISM::console'Available Commands:');
                        PRISM::console'    h - show host info');
                        PRISM::console'    I - re-initialise PRISM (reload ini files / reconnect to hosts / reset http socket');
                        PRISM::console'    p - show plugin info');
                        PRISM::console'    x - exit PRISM');
                        PRISM::console'    w - show www connections');
                        PRISM::console'    c - show command list');
                    }
                }

            } // End while(numReady)

            // No need to do the maintenance check every turn
            if ($this->_nextMaintenance > time ()) {
                continue;
            }

            $this->_nextMaintenance = time () + MAINTENANCE_INTERVAL;

            if (!$this->hosts->maintenance()) {
                $this->isRunning = false;
            }

            $this->http->maintenance();
            PHPParser::cleanSessions();

        } // End while(isRunning)
    }

    private function _updateSelectTimeOut(&$sleep, &$uSleep)
    {
        $sleep = 1;
        $uSleep = null;

        $sleepTime = null;
        foreach ($this->plugins->getPlugins() as $plugin => $object) {
            $timeout = $object->executeTimers();

            if ($timeout < $sleepTime) {
                $sleepTime = $timeout;
            }
        }

        // If there are no timers set or the next timeout is more then a second away, set the Sleep to 1 & uSleep to null.
        if ($sleepTime == null || $timeout < $sleepTime) {
            $sleepTime = $timeout;
        } else {    // Set the timeout to the delta of now as compared to the next timer.
            list($sleep, $uSleep) = explode('.', sprintf('%1.6f', $timeNow - $sleepTime));
            if (($sleep >= 1 && $uSleep >= 1) || $uSleep >= 1000000) {
                $sleep = 1;
                $uSleep = null;
            }
        }
    }

    public function __destruct()
    {
        // What makes this shutdown particularly safe?
        PRISM::console('Safe shutdown: ' . date($this->config->cvars['logFormat']));
    }

    public function console($line, $EOL = true)
    {
        // Add log to file
        // Effected by PRISM_LOG_MODE && PRISM_LOG_FILE_MODE
        echo $line . (($EOL) ? PHP_EOL : '');
    }

    public function get_dir_structure($path, $recursive = TRUE, $ext = NULL)
    {
        $return = NULL;
        if (!is_dir($path)) {
            trigger_error('$path is not a directory!', E_USER_WARNING);
            return FALSE;
        }
        if ($handle = opendir($path)) {
            while (FALSE !== ($item = readdir($handle))) {
                if ($item != '.' && $item != '..') {
                    if (is_dir($path . $item)) {
                        if ($recursive) {
                            $return[$item] = PRISM::get_dir_structure($path . $item . '/', $recursive, $ext);
                        } else {
                            $return[$item] = array();
                        }
                    } else {
                        if ($ext != null && strrpos($item, $ext) !== FALSE) {
                            $return[] = $item;
                        }
                    }
                }
            }
            closedir($handle);
        }
        return $return;
    }

    // check if path1 is part of path2 (ie. if path1 is a base path of path2)
    public function isDirInDir($path1, $path2)
    {
        $p1 = explode('/', $path1);
        $p2 = explode('/', $path2);

        foreach ($p1 as $index => $part) {
            if ($part === '')
                continue;
            if (!isset($p2[$index]) || $part != $p2[$index])
                return false;
        }

        return true;
    }

    public function findPHPLocation($windows = false)
    {
        $phpLocation = '';

        if ($windows) {
            PRISM::console('Trying to find the location of php.exe');

            // Search in current dir first.
            $exp = explode("\r\n", shell_exec('dir /s /b php.exe'));
            if (preg_match('/^.*\\\php\.exe$/', $exp[0])) {
                $phpLocation = $exp[0];
            } else {
                // Do a recursive search on this whole drive.
                chdir('/');
                $exp = explode("\r\n", shell_exec('dir /s /b php.exe'));

                if (preg_match('/^.*\\\php\.exe$/', $exp[0])) {
                    $phpLocation = $exp[0];
                }

                chdir(ROOTPATH);
            }
        } else {
            $exp = explode(' ', shell_exec('whereis php'));
            $count = count($exp);
            if ($count == 1)                // Some *nix's output is only the path
                $phpLocation = $exp[0];
            else if ($count > 1)            // FreeBSD for example has more info on the line, like :
                $phpLocation = $exp[1];     // php: /user/local/bin/php /usr/local/man/man1/php.1.gz
        }

        return $phpLocation;
    }

    public function validatePHPFile($file)
    {
        // Validate script
        $fileContents = file_get_contents($file);
        if (!eval('return true;'.preg_replace(array('/^<\?(php)?/', '/\?>$/'), '', $fileContents)))
            return array(false, array('Errors parsing '.$file));

        // Validate any require_once or include_once files.
    //  $matches = array();
    //  preg_match_all('/(include_once|require_once)\s*\(["\']+(.*)["\']+\)/', $fileContents, $matches);
    //
    //  foreach ($matches[2] as $include)
    //  {
    //      PRISM::console($include);
    //      $result = validatePHPFile($include);
    //      if ($result[0] == false)
    //          return $result;
    //  }

        return array(true, array());
    }

    public function flagsToInteger($flagsString = '')
    {
        # We don't have anything to parse.
        if ($flagsString == '')
            return FALSE;

        $flagsBitwise = 0;
        for ($chrPointer = 0, $strLen = strlen($flagsString); $chrPointer < $strLen; ++$chrPointer) {
            # Convert this charater to it's ASCII int value.
            $char = ord($flagsString{$chrPointer});

            # We only want a (ASCII = 97) through z (ASCII 122), nothing else.
            if ($char < 97 || $char > 122)
                continue;

            # Check we have already set that flag, if so skip it!
            if ($flagsBitwise & (1 << ($char - 97)))
                continue;

            # Add the value to our $flagBitwise intager.
            $flagsBitwise += (1 << ($char - 97));
        }
        return $flagsBitwise;
    }

    public function flagsToString($flagsBitwise = 0)
    {
        $flagsString = '';
        if ($flagsBitwise == 0)
            return $flagsString;

        # This makes sure we only handle the flags we know by unsetting any unknown bits.
        $flagsBitwise = $flagsBitwise & ADMIN_ALL;

        # Converts bits to the char forms.
        for ($i = 0; $i < 26; ++$i)
            $flagsString .= ($flagsBitwise & (1 << $i)) ? chr($i + 97) : NULL;

        return $flagsString;
    }

}
