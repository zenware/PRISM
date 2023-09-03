<?php
/* PHPInSimMod
*
* by the PHPInSimMod Development Team.
*
*/

/* Defines */
// PRISM
namespace PRISM;

define('PRISM_DEBUG_CORE',        1);            # Shows Debug Messages From the Core
define('PRISM_DEBUG_SOCKETS',    2);            # Shows Debug Messages From the Sockets Module
define('PRISM_DEBUG_MODULES',    4);            # Shows Debug Messages From the all Modules
define('PRISM_DEBUG_PLUGINS',    8);            # Shows Debug Messages From the Plugins
define('PRISM_DEBUG_ALL',        15);        # Shows Debug Messages From All

define('MAINTENANCE_INTERVAL',     2);            # The frequency in seconds to do connection maintenance checks.

// Return Codes:
define('PLUGIN_CONTINUE',        0);            # Plugin passes through operation. Whatever called it continues.
define('PLUGIN_HANDLED',        1);            # Plugin halts continued operation. Plugins following in the plugins.ini won't be called.
define('PLUGIN_STOP',            2);            # Plugin stops timer from triggering again in the future.

error_reporting(E_ALL);
ini_set('display_errors',        'true');

// ROOTPATH is intentionally being configured as the root of the git repository rather than the root of this file.
// This is so configs/data/etc. can remain where they are while refactoring.
define('ROOTPATH', dirname(dirname((string) realpath(__FILE__))));

// the REQUIRED modules for PRISM.
use PRISM\Modules;
use PRISM\Modules\Utils;
require_once(ROOTPATH . '/modules/prism_packets.php');
require_once(ROOTPATH . '/modules/prism_hosts.php');
require_once(ROOTPATH . '/modules/prism_statehandler.php');
require_once(ROOTPATH . '/modules/prism_http.php');
require_once(ROOTPATH . '/modules/prism_telnet.php');
require_once(ROOTPATH . '/modules/prism_translations.php');
require_once(ROOTPATH . '/modules/prism_plugins.php');

/**
 * PHPInSimMod
 * @package PRISM
 * @author Dygear (Mark Tomlin) <Dygear@gmail.com>
 * @author ripnet (Tom Young) <ripnet@gmail.com>
 * @author morpha (Constantin Köpplinger) <morpha@xigmo.net>
 * @author Victor (Victor van Vlaardingen) <vic@lfs.net>
 * @author GeForz (Kai Lochbaum)
 * @author T3charmy (Macgyver Hoferkamp)
 * @author zenware (Jay Looney)
 * @author FlameCZE
 * @author Povo
*/
class PHPInSimMod
{
    const VERSION = '0.6.1';
    const ROOTPATH = ROOTPATH;

    /* Run Time Arrays */
    public Modules\ConfigHandler $config;
    public \PRISM\HostHandler $hosts;
    public \PRISM\HttpHandler $http;
    public \PRISM\TelnetHandler $telnet;
    public \PRISM\PluginHandler $plugins;
    public Modules\AdminHandler $admins;

    # Time outs
    // Note: I'm assuming these are primarily int|float, but I'm not certain.
    private mixed $sleep = null;
    private mixed $uSleep = null;

    private int $nextMaintenance = 0;
    public bool $isWindows = false;

    // Main while loop will run as long as this is set to true.
    private bool $isRunning = false;

    // Real Magic Functions
    public function __construct()
    {
        // https://www.php.net/manual/en/function.set-error-handler.php
        set_error_handler(__CLASS__ . '::_errorHandler', E_ALL | E_STRICT);

        // Windows OS check
        $this->isWindows = (PHP_OS_FAMILY == 'Windows');

        $this->config    = new Modules\ConfigHandler();
        $this->hosts    = new \PRISM\HostHandler();
        $this->plugins    = new \PRISM\PluginHandler();
        $this->http        = new \PRISM\HttpHandler();
        $this->telnet    = new \PRISM\TelnetHandler();
        $this->admins    = new Modules\AdminHandler();
    }

    public static function _errorHandler(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        // https://www.php.net/manual/en/function.set-error-handler.php
        // errcontext is deprecated in PHP 7.2.0 and REMOVED in PHP 8.0.0
        # This error code is not included in error_reporting
        if (!(error_reporting() & $errno))
            return false;

        $andExit = false;

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

        echo "\t$errstr in $errfile on line $errline".PHP_EOL;

        $trace = debug_backtrace();
        foreach ($trace as $index => $call) {
            if ($call['function'] == 'main') break;
            if ($index > 0 AND isset($call['file']) AND isset($call['line'])) {
                Utils::console("\t".$index.' :: '.$call['function'].' in '.$call['file'].':'.$call['line']);
            }
        }

        if ($andExit)
            exit(1);

        # Don't execute PHP internal error handler

        return true;
    }

    public function initialise(): void
    {
        // Set the timezone
        if (isset($this->config->cvars['defaultTimeZone']))
            date_default_timezone_set($this->config->cvars['defaultTimeZone']);
        else {
            # I know, I'm using error suppression, but I swear it's appropriate!
            $timeZoneGuess = @date_default_timezone_get();
            date_default_timezone_set($timeZoneGuess);
            unset($timeZoneGuess);
        }

        // Initialise handlers (load config files)
        if (!$this->config->initialise() OR
            !$this->hosts->initialise() OR
            !$this->http->initialise() OR
            !$this->telnet->initialise() OR
            !$this->admins->initialise() OR
            !$this->plugins->initialise())
        {
            Utils::console('Fatal error encountered. Exiting...');
            exit(1);
        }

        $pluginsLoaded = $this->plugins->loadPlugins();

        if ($this->config->cvars['debugMode'] & PRISM_DEBUG_CORE) {
            if ($pluginsLoaded == 0)
                Utils::console('No Plugins Loaded');
            else if ($pluginsLoaded == 1)
                Utils::console('One Plugin Loaded');
            else
                Utils::console("{$pluginsLoaded} Plugins Loaded.");
        }
    }

    public function start(): void
    {
        if ($this->isRunning)
            return;

        $this->isRunning = true;
        $this->nextMaintenance = time() + MAINTENANCE_INTERVAL;

        $this->main();
    }

    protected function main(): void
    {
        while ($this->isRunning === true) {
            // Setup our listen arrays
            $sockReads = array();
            $sockWrites = array();
            $socketExcept = array();

            if (!$this->isWindows)
                $sockReads[] = STDIN;

            // Fill $sockReads array with sockets to read from.
            // While at it, check if we need to connect to any of the hosts.
            $this->hosts->getSelectableSockets($sockReads, $sockWrites);
            $this->http->getSelectableSockets($sockReads, $sockWrites);
            $this->telnet->getSelectableSockets($sockReads, $sockWrites);

            // Update timeout if there are timers waiting to be fired.
            $this->updateSelectTimeOut($this->sleep, $this->uSleep);

            # Error suppression used because this function returns a "Invalid CRT parameters detected" only on Windows.
            // Consider catching the warning.
            $numReady = @stream_select($sockReads, $sockWrites, $socketExcept, $this->sleep, $this->uSleep);

            if (empty($sockReads) || empty($sockWrites)) {
                throw new \Exception("Could not find at least, both a selectable read and write socket. Cannot continue execution.");
            }

            // Keep looping until you've handled all activities on the sockets.
            while ($numReady > 0) {
                $numReady -= $this->hosts->checkTraffic($sockReads, $sockWrites);
                $numReady -= $this->http->checkTraffic($sockReads, $sockWrites);
                $numReady -= $this->telnet->checkTraffic($sockReads, $sockWrites);

                // KB input
                if (in_array (STDIN, $sockReads)) {
                    $numReady--;
                    $bytes = fread(STDIN, STREAM_READ_BYTES);
                    if (!$bytes) { continue; }
                    
                    $kbInput = trim($bytes);

                    // Split up the input
                    $exp = explode (' ', $kbInput);

                    // Process the command (the first char or word of the line)
                    switch ($exp[0]) {
                        case 'c':
                            Utils::console(sprintf('%32s - %64s', 'COMMAND', 'DESCRIPTION'));
                            foreach ($this->plugins->getPlugins() as $details) {
                                foreach ($details->sayCommands as $command => $detail) {
                                    Utils::console(sprintf('%32s - %64s', $command, $detail['info']));
                                }
                            }

                            break;
                        case 'h':
                            Utils::console(sprintf('%14s %28s:%-5s %8s %22s', 'Host ID', 'IP', 'PORT', 'UDPPORT', 'STATUS'));
                            foreach ($this->hosts->getHostsInfo() as $host) {
                                $status = (($host['connStatus'] == CONN_CONNECTED) ? '' : (($host['connStatus'] == CONN_VERIFIED) ? 'VERIFIED &' : ' NOT')).' CONNECTED';
                                $socketType = (($host['socketType'] == SOCKTYPE_TCP) ? 'tcp://' : 'udp://');
                                Utils::console(sprintf('%14s %28s:%-5s %8s %22s', $host['id'], $socketType.$host['ip'], $host['port'], $host['udpPort'], $status));
                            }
                            break;

                        case 'I':
                            Utils::console('RE-INITIALISING PRISM...');
                            $this->initialise();
                            break;

                        case 'p':
                            Utils::console(sprintf('%28s %8s %24s %64s', 'NAME', 'VERSION', 'AUTHOR', 'DESCRIPTION'));
                            foreach ($this->plugins->getPlugins() as $plugin) {
                                Utils::console(sprintf("%28s %8s %24s %64s", $plugin::NAME, $plugin::VERSION, $plugin::AUTHOR, $plugin::DESCRIPTION));
                            }
                            break;

                        case 'x':
                            $this->isRunning = false;
                            break;

                        case 'w':
                            Utils::console(sprintf('%15s:%5s %5s', 'IP', 'PORT', 'LAST ACTIVITY'));
                            foreach ($this->http->getHttpInfo() as $v) {
                                $lastAct = time() - $v['lastActivity'];
                                Utils::console(sprintf('%15s:%5s %13d', $v['ip'], $v['port'], $lastAct));
                            }
                            Utils::console('Counted '.$this->http->getHttpNumClients().' http client'.(($this->http->getHttpNumClients() == 1) ? '' : 's'));
                            break;

                        default :
                            Utils::console('Available Commands:');
                            Utils::console('    h - show host info');
                            Utils::console('    I - re-initialise PRISM (reload ini files / reconnect to hosts / reset http socket');
                            Utils::console('    p - show plugin info');
                            Utils::console('    x - exit PHPInSimMod');
                            Utils::console('    w - show www connections');
                            Utils::console('    c - show command list');
                    }
                }

            } // End while(numReady)

            // No need to do the maintenance check every turn
            if ($this->nextMaintenance > time())
                continue;
            $this->nextMaintenance = time() + MAINTENANCE_INTERVAL;
            if (!$this->hosts->maintenance())
                $this->isRunning = false;
            $this->http->maintenance();
            Modules\PHPParser::cleanSessions();

        } // End while(isRunning)
    }

    /**
     * Undocumented function
     * 
     * This seems like it's meant to trigger/update any timers registered to plugins.
     * However it seems terribly busted. This is only called from inside this
     * class. Consider for removal.
     *
     * @param mixed $sleep
     * @param mixed $uSleep
     * @return void
     */
    private function updateSelectTimeOut(mixed &$sleep, mixed &$uSleep)
    {
        $sleep = 1;
        $uSleep = null;
        $timeout = null;
        $sleepTime = null;

        foreach ($this->plugins->getPlugins() as $_ => $object) {
            $timeout = $object->executeTimers();

            if ($timeout < $sleepTime)
                $sleepTime = $timeout;
        }

        // If there are no timers set or the next timeout is more then a second away, set the Sleep to 1 & uSleep to null.
        // Set the timeout to the delta of now as compared to the next timer.
        if (!($timeout < $sleepTime)) {
            $timeNow = time();
            list($sleep, $uSleep) = explode('.', sprintf('%1.6f', $timeNow - (float) $sleepTime));
            if (($sleep >= 1 && $uSleep >= 1) || $uSleep >= 1000000) {
                $sleep = 1;
                $uSleep = null;
            }
        }
    }

    public function __destruct()
    {
        Utils::console('Safe shutdown: ' . date($this->config->cvars['logFormat']));
    }
}
