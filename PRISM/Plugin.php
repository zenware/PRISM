<?php
/**
 * Plugin.php - Extended by all PRISM plugins.
 *
 * @category   Superclass
 * @package    PRISM
 * @subpackage Plugin
 * @author     zenware (Jay Looney) <jay.m.looney@gmail.com>
 * @license    http://opensource.org/licenses/MIT MIT License
 */

namespace PRISM\Plugin;
use PRISM\Module\Timers;

/**
 * PRISM - Plugin
 *
 * @category   Module
 * @package    PRISM
 * @subpackage Plugin
 * @author     Dygear (Mark Tomlin) <Dygear@gmail.com>
 * @author     zenware (Jay Looney) <jay.m.looney@gmail.com>
 * @license    http://opensource.org/licenses/MIT MIT License
 */
abstract class Plugin extends Timers
{
    /** These consts should _ALWAYS_ be defined in your classes. */
    /* const NAME;            */
    /* const DESCRIPTION;    */
    /* const AUTHOR;        */
    /* const VERSION;        */

    /** Properties */
    public $callbacks = array(
    );
    // Callbacks
    public $consoleCommands = array();
    public $insimCommands = array();
    public $localCommands = array();
    public $sayCommands = array();

    /** Internal Methods */
    private function getCallback($cmdsArray, $cmdString)
    {
        // Quick Lookup (Commands without Args)
        if (isset($cmdsArray[$cmdString])) {
            return $cmdsArray[$cmdString];
        }

        // Through Lookup (Commands with Args)
        foreach ($cmdsArray as $cmd => $details)  {
            // Due to the nature of these commands, we have to check all instances for matches.
            if (strpos($cmdString, $cmd) === 0) { // Check if the string STARTS with our command.
                return $details;
            }
        }

        return false;
    }

    /** Send Methods */
    protected function sendPacket(Struct $packetClass)
    {
        global $PRISM;
        return $PRISM->hosts->sendPacket($packetClass);
    }

    /** Handle Methods */
    // This is the yang to the registerSayCommand & registerLocalCommand function's Yin.
    public function handleCmd(IS_MSO $packet)
    {
        if ($packet->UserType == MSO_PREFIX && $cmdString = substr($packet->Msg, $packet->TextStart + 1) && $callback = $this->getCallback($this->sayCommands, $cmdString) && $callback !== false) {
            if ($this->canUserAccessCommand($packet->UCID, $callback)) {
                $this->$callback['method']($cmdString, $packet->UCID, $packet);
            } else {
                console("{$this->getClientByUCID($packet->UCID)->UName} tried to access {$callback['method']}.");
            }
        } elseif ($packet->UserType == MSO_O && $callback = $this->getCallback($this->localCommands, $packet->Msg) && $callback !== false) {
            if ($this->canUserAccessCommand($packet->UCID, $callback)) {
                $this->$callback['method']($packet->Msg, $packet->UCID, $packet);
            } else {
                console("{$this->getClientByUCID($packet->UCID)->UName} tried to access {$callback['method']}.");
            }
        }
    }

    // This is the yang to the registerInsimCommand function's Yin.
    public function handleInsimCmd(IS_III $packet)
    {
        if ($callback = $this->getCallback($this->insimCommands, $packet->Msg) && $callback !== false) {
            if ($this->canUserAccessCommand($packet->UCID, $callback)) {
                $this->$callback['method']($packet->Msg, $packet->UCID, $packet);
            } else {
                console("{$this->getClientByUCID($packet->UCID)->UName} tried to access {$callback['method']}.");
            }
        }
    }

    // This is the yang to the registerConsoleCommand function's Yin.
    public function handleConsoleCmd($string)
    {
        if ($callback = $this->getCallback($this->consoleCommands, $string) && $callback !== false) {
            $this->$callback['method']($string, null);
        }
    }

    /** Access Level Related Functions */
    protected function canUserAccessCommand($UCID, $cmd)
    {
        // Hosts are automatic admins so due to their nature, they have full access.
        // Commands that have no premission level don't require this check.
        if ($UCID == 0 OR $cmd['accessLevel'] == -1) {
            return true;
        }

        global $PRISM;
        $adminInfo = $PRISM->admins->getAdminInfo($this->getClientByUCID($UCID)->UName);
        return ($cmd['accessLevel'] & $adminInfo['accessFlags']) ? true : false;
    }

    // Returns true if a user's access level is equal or greater then the required level.
    protected function checkUserLevel($userLevel, $accessLevel)
    {
        return ($userLevel & $accessLevel) ? true : false;
    }

    /** Register Methods */
    /**
     * Directly registers a packet to be handled by a callback method within the plugin.
     *
     * @param string $callbackMethod This is the name of the callback method within the plugin
     * @param int    $PacketType     I'm not sure exactly what this is...
     *
     * @return none
     */
    protected function registerPacket($callbackMethod, $PacketType)
    {
        $this->callbacks[$PacketType][] = $callbackMethod;
        $PacketTypes = func_get_args();

        for ($i = 2, $j = count($PacketTypes); $i < $j; ++$i) {
            $this->callbacks[$PacketTypes[$i]][] = $callbackMethod;
        }
    }

    /**
     * Setup the callback method trigger to accept a command that could come from anywhere.
     *
     * @param string $cmd                       The command name
     * @param string $callbackMethod            Name of the callback method
     * @param string $info                      Info about the command
     * @param int    $defaultAdminLevelToAccess Permission Level
     *
     * @see Plugin::registerInsimCommand();
     * @see Plugin::registerLocalCommand();
     * @see Plugin::registerSayCommand();
     *
     * @return none
     */
    protected function registerCommand($cmd, $callbackMethod, $info = '', $defaultAdminLevelToAccess = -1)
    {
        $this->registerInsimCommand($cmd, $callbackMethod, $info, $defaultAdminLevelToAccess);
        $this->registerLocalCommand($cmd, $callbackMethod, $info, $defaultAdminLevelToAccess);
        $this->registerSayCommand($cmd, $callbackMethod, $info, $defaultAdminLevelToAccess);
    }

    /**
     * Any command that comes from the PRISM console.
     */
    protected function registerConsoleCommand($cmd, $callbackMethod, $info = '')
    {
        if (!isset($this->callbacks['STDIN']) && !isset($this->callbacks['STDIN']['handleConsoleCmd'])) {
            // We don't have any local callback hooking to the STDIN stream, make one.
            $this->registerPacket('handleInsimCmd', 'STDIN');
        }

        $this->consoleCommands[$cmd] = array('method' => $callbackMethod, 'info' => $info);
    }

    /**
     * Any command that comes from the "/i" type. (III)
     *
     * @param string $cmd                       The command name
     * @param string $callbackMethod            Name of the callback method
     * @param string $info                      Info about the command
     * @param int    $defaultAdminLevelToAccess Permission Level
     *
     * @see Plugin::registerPacket();
     *
     * @return none
     */
    protected function registerInsimCommand($cmd, $callbackMethod, $info = '', $defaultAdminLevelToAccess = -1)
    {
        if (!isset($this->callbacks[ISP_III]) && !isset($this->callbacks[ISP_III]['handleInsimCmd'])) {
            // We don't have any local callback hooking to the ISP_III packet, make one.
            $this->registerPacket('handleInsimCmd', ISP_III);
        }

        $this->insimCommands[$cmd] = array('method' => $callbackMethod, 'info' => $info, 'accessLevel' => $defaultAdminLevelToAccess);
    }

    /**
     * Any command that comes from the "/o" type. (MSO->Flags = MSO_O)
     *
     * @param string $cmd                       The command name
     * @param string $callbackMethod            Name of the callback method
     * @param string $info                      Info about the command
     * @param int    $defaultAdminLevelToAccess Permission Level
     *
     * @see Plugin::registerPacket();
     *
     * @return none
     */
    protected function registerLocalCommand($cmd, $callbackMethod, $info = '', $defaultAdminLevelToAccess = -1)
    {
        if (!isset($this->callbacks[ISP_MSO]) && !isset($this->callbacks[ISP_MSO]['handleCmd'])) {
            // We don't have any local callback hooking to the ISP_MSO packet, make one.
            $this->registerPacket('handleCmd', ISP_MSO);
        }

        $this->localCommands[$cmd] = array('method' => $callbackMethod, 'info' => $info, 'accessLevel' => $defaultAdminLevelToAccess);
    }

    /**
     * Any say event with prefix charater (ISI->Prefix) with this command type. (MSO->Flags = MSO_PREFIX)
     *
     * @param string $cmd                       The command name
     * @param string $callbackMethod            Name of the callback method
     * @param string $info                      Info about the command
     * @param int    $defaultAdminLevelToAccess Permission Level
     *
     * @see Plugin::registerPacket
     *
     * @return none
     */
    protected function registerSayCommand($cmd, $callbackMethod, $info = '', $defaultAdminLevelToAccess = -1)
    {
        if (!isset($this->callbacks[ISP_MSO]) && !isset($this->callbacks[ISP_MSO]['handleCmd'])) {
            // We don't have any local callback hooking to the ISP_MSO packet, make one.
            $this->registerPacket('handleCmd', ISP_MSO);
        }

        $this->sayCommands[$cmd] = array('method' => $callbackMethod, 'info' => $info, 'accessLevel' => $defaultAdminLevelToAccess);
    }

    /** Internal Functions */
    protected function getCurrentHostId()
    {
        global $PRISM;
        return $PRISM->hosts->curHostID;
    }

    protected function getHostId($hostID = null)
    {
        if ($hostID === null) {
            return $this->getCurrentHostId();
        }

        return $hostID;
    }

    protected function getHostInfo($hostID = null)
    {
        global $PRISM;

        if (($host = $PRISM->hosts->getHostById($hostID)) && $host !== null) {
            return $host;
        }

        return null;
    }

    protected function getHostState($hostID = null)
    {
        global $PRISM;

        if (($state = $PRISM->hosts->getStateById($hostID)) && $state !== null) {
            return $state;
        }

        return null;
    }

    /** Server Methods */
    protected function serverGetName()
    {
        if ($this->getHostState() !== null) {
            return $this->getHostState()->HName;
        }

        return null;
    }

    /** Client & Player */
    protected function &getPlayerByPLID(&$PLID, $hostID = null)
    {
        if (($players = $this->getHostState($hostID)->players) && $players !== null && isset($players[$PLID])) {
            return $players[$PLID];
        }

        return null;
    }

    protected function &getPlayerByUCID(&$UCID, $hostID = null)
    {
        if (($clients =& $this->getHostState($hostID)->clients) && $clients !== null && isset($clients[$UCID])) {
            return $clients[$UCID]->players;
        }

        return null;
    }

    protected function &getPlayerByPName(&$PName, $hostID = null)
    {
        if (($players = $this->getHostState($hostID)->players) && $players !== null) {
            foreach ($players as $plid => $player) {
                if (strToLower($player->PName) == strToLower($PName)) {
                    return $player;
                }
            }
        }

        return null;
    }

    protected function &getPlayerByUName(&$UName, $hostID = null)
    {
        if (($players = $this->getHostState($hostID)->players) && $players !== null) {
            foreach ($players as $plid => $player) {
                if (strToLower($player->UName) == strToLower($UName)) {
                    return $player;
                }
            }
        }

        return null;
    }

    protected function &getClientByPLID(&$PLID, $hostID = null)
    {
        if (($players = $this->getHostState($hostID)->players) && $players !== null && isset($players[$PLID])) {
            $UCID = $players[$PLID]->UCID; // As so to avoid Indirect modification of overloaded property NOTICE;
            return $this->getClientByUCID($UCID);
        }

        return $return;
    }

    protected function &getClientByUCID(&$UCID, $hostID = null)
    {
        if (($clients =& $this->getHostState($hostID)->clients) && $clients !== null && isset($clients[$UCID])) {
            return $clients[$UCID];
        }

        return null;
    }

    protected function &getClientByPName(&$PName, $hostID = null)
    {
        if (($players = $this->getHostState($hostID)->players) && $players !== null) {
            foreach ($players as $plid => $player) {
                if (strToLower($player->PName) == ($PName)) {
                    $UCID = $player->UCID; // As so to avoid Indirect modification of overloaded property NOTICE;
                    return $this->getClientByUCID($UCID);
                }
            }
        }

        return null;
    }

    protected function &getClientByUName(&$UName, $hostID = null)
    {
        if (($clients = $this->getHostState($hostID)->clients) && $clients !== null) {
            foreach ($clients as $ucid => $client) {
                if (strToLower($client->UName) == strToLower($UName)) {
                    return $client;
                }
            }
        }

        return null;
    }

    // Is
    protected function isHost(&$username, $hostID = null)
    {
        return ($this->getHostState($this->getHostId($hostID))->clients[0]->UName == $username) ? true : false;
    }

    protected function isAdmin(&$username, $hostID = null)
    {
//        global $PRISM;
        // Check the user is defined as an admin.
//        if (!$PRISM->admins->adminExists($username))
//            return false;

        // set the $hostID;
        if ($hostID === null) {
            $hostID = $this->getHostId($hostID);
        }

        // Check the user is defined as an admin on all or the host current host.
//        $adminInfo = $PRISM->admins->getAdminInfo($username);
        return ($this->isAdminGlobal($username) || $this->isAdminLocal($username, $hostID)) ? true : false;
    }

    protected function isAdminGlobal(&$username)
    {
        global $PRISM;
        // Check the user is defined as an admin.
        if (!$PRISM->admins->adminExists($username)) {
            return false;
        }

        $adminInfo = $PRISM->admins->getAdminInfo($username);
        return (strpos($adminInfo['connection'], '*') !== false) ? true : false;
    }

    protected function isAdminLocal(&$username, $hostID = null)
    {
        global $PRISM;

        // Check the user is defined as an admin.
        if (!$PRISM->admins->adminExists($username)) {
            return false;
        }

        // set the $hostID;
        if ($hostID === null) {
            $hostID = $PRISM->hosts->curHostID;
        }

        // Check the user is defined as an admin on the host current host.
        $adminInfo = $PRISM->admins->getAdminInfo($username);
        return ((strpos($adminInfo['connection'], $hostID) !== false) !== false) ? true : false;
    }

    protected function isImmune(&$username)
    {
        global $PRISM;
        // Check the user is defined as an admin.
        if (!$PRISM->admins->adminExists($username)) {
            return false;
        }

        // Check the user is defined as an admin on the host current host.
        $adminInfo = $PRISM->admins->getAdminInfo($username);
        return ($adminInfo['accessFlags'] & ADMIN_IMMUNITY) ? true : false;
    }
}
