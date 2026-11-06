<?php
/**
 * Minimal PRISM bootstrap for tests.
 *
 * Deliberately does not require PHPInSimMod.php: that file starts the daemon,
 * opens sockets and can run the interactive setup. Tests load only the classes
 * they need and install a fake $PRISM.
 *
 * Must stay parseable by PHP 5.3/5.6 and PHP 8.x. Avoid short array syntax,
 * scalar type declarations, ??, match, arrow functions and typed properties.
 */

if (!defined('ROOTPATH')) {
    define('ROOTPATH', dirname(__DIR__));
}

if (!ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}

require_once ROOTPATH . '/modules/prism_version.php';

if (!class_exists('PHPInSimMod', false)) {
    class PHPInSimMod
    {
        const VERSION = PRISM_VERSION;
    }
}

require_once ROOTPATH . '/modules/prism_functions.php';
require_once ROOTPATH . '/modules/prism_packets.php';
require_once ROOTPATH . '/modules/prism_timers.php';
require_once ROOTPATH . '/modules/prism_translations.php';
require_once ROOTPATH . '/modules/prism_sectionhandler.php';
require_once ROOTPATH . '/modules/prism_hosts.php';      // defines STREAM_*_BYTES
require_once ROOTPATH . '/modules/prism_http.php';       // defines HTTP_AUTH_REALM, loads PHPParser
require_once ROOTPATH . '/modules/prism_admins.php';
require_once ROOTPATH . '/modules/prism_geometry.php';
require_once ROOTPATH . '/modules/prism_pth.php';
require_once ROOTPATH . '/modules/prism_plugins.php';

class PrismTestConfig
{
    public $cvars = array(
        'prefix'    => '!',
        'debugMode' => 0,
        'secToken'  => 'test-sec-token',
    );
}

class PrismTestHostInfo
{
    public function getFlags() { return 0; }
    public function isRelay() { return false; }
    public function isAdmin() { return false; }
    public function getIP() { return '127.0.0.1'; }
    public function getPort() { return 0; }
    public function getUdpPort() { return 0; }
}

class PrismTestState
{
    public $State = 0;
    public $HName = '';
    public $clients = array();
    public $players = array();
}

class PrismTestHosts
{
    public $curHostID = 'test-host';
    public $sent = array();

    public function sendPacket($packet, $hostId = null)
    {
        $this->sent[] = array('host' => $hostId, 'packet' => $packet);
        return true;
    }

    public function getCurrentHost()
    {
        return $this->curHostID;
    }

    public function getHostById($hostId = null)
    {
        return new PrismTestHostInfo();
    }

    public function getStateById($hostId = null)
    {
        return new PrismTestState();
    }

    public function getHostsInfo()
    {
        return array();
    }
}

$PRISM = new stdClass();
$PRISM->config = new PrismTestConfig();
$PRISM->hosts = new PrismTestHosts();
$PRISM->admins = new AdminHandler();
$PRISM->plugins = null;
