<?php
/**
 * PRISM - Plugin Handler Module
 * Heyoh~ this is going to need some hardcore fixing
 *
 * @category   Module
 * @package    PRISM
 * @subpackage Module\PluginHandler
 * @author     Dygear (Mark Tomlin) <Dygear@gmail.com>
 * @author     zenware (Jay Looney) <jay.m.looney@gmail.com>
 * @license    http://opensource.org/licenses/MIT MIT License
 * @link       https://github.com/zenware/PRISM/blob/devel/Plugin/prism_plugins.php
 */

namespace PRISM\Module;
use PRISM\Module\SectionHandler;

define('PRINT_CHAT',    (1 << 0));        // 1
define('PRINT_RCM',     (1 << 1));        // 2
define('PRINT_NUM',     (1 << 2)-1);    // 4 - 1
define('PRINT_CONTEXT', PRINT_NUM);        // 3

/**
 * PRISM - Plugin Handler Module
 *
 * @category   Module
 * @package    PRISM
 * @subpackage Module\PluginHandler
 * @author     Dygear (Mark Tomlin) <Dygear@gmail.com>
 * @author     zenware (Jay Looney) <jay.m.looney@gmail.com>
 * @license    http://opensource.org/licenses/MIT MIT License
 */
class PluginHandler extends SectionHandler
{
    private $plugins    = array();            // Stores references to the plugins we've spawned.
    private $pluginvars = array();

    public function __construct()
    {
        $this->iniFile = 'plugins.ini';
    }

    public function init()
    {
        global $PRISM;

        $this->pluginvars = array();

        if ($this->loadIniFile($this->pluginvars)) {
            foreach ($this->pluginvars as $pluginID => $v) {
                if (!is_array($v)) {
                    console('Section error in '.$this->iniFile.' file!');
                    return false;
                }
            }

            if ($PRISM->config->cvars['debugMode'] & PRISM_DEBUG_CORE) {
                console('Loaded '.$this->iniFile);
            }

            // Parse useHosts values of plugins
            foreach ($this->pluginvars as $pluginID => $details) {
                if (isset($details['useHosts'])) {
                    $this->pluginvars[$pluginID]['useHosts'] = explode(',', $details['useHosts']);
                } else {
                    unset($this->pluginvars[$pluginID]);
                }
            }
        } else {
            // We ask the client to manually input the plugin details here.
            // What the fuck is this.
            include_once ROOTPATH . '/modules/prism_interactive.php';
            Interactive::queryPlugins($this->pluginvars, $PRISM->hosts->getHostsInfo());

            if ($this->createIniFile('PRISM Plugins', $this->pluginvars)) {
                console('Generated config/'.$this->iniFile);
            }

            // Parse useHosts values of plugins
            foreach ($this->pluginvars as $pluginID => $details) {
                $this->pluginvars[$pluginID]['useHosts'] = explode('","', $details['useHosts']);
            }
        }

        return true;
    }

    public function loadPlugins()
    {
        global $PRISM;

        $loadedPluginCount = 0;

        if ($PRISM->config->cvars['debugMode'] & PRISM_DEBUG_CORE) {
            console('Loading plugins');
        }

        $pluginPath = ROOTPATH.'/plugins';

        if (($pluginFiles = get_dir_structure($pluginPath, false, '.php')) === null) {
            if ($PRISM->config->cvars['debugMode'] & PRISM_DEBUG_CORE) {
                console('No plugins found in the directory.');
            }

            // As we can't find any plugin files, we invalidate the the ini settings.
            $this->pluginvars = null;
        }

        // If there are no plugins, then don't loop through the list.
        if ($this->pluginvars == null) {
            return true;
        }

        // Find what plugin files have ini entrys
        foreach ($this->pluginvars as $pluginSection => $pluginHosts) {
            $pluginFileHasPluginSection = false;

            foreach ($pluginFiles as $pluginFile) {
                if ("$pluginSection.php" == $pluginFile) {
                    $pluginFileHasPluginSection = true;
                }
            }

            // Remove any pluginini value who does not have a file associated with it.
            if ($pluginFileHasPluginSection === false) {
                unset($this->pluginvars[$pluginSection]);
                continue;
            }

            // Load the plugin file.
            if ($PRISM->config->cvars['debugMode'] & PRISM_DEBUG_CORE) {
                console("Loading plugin: $pluginSection");
            }

            include_once "$pluginPath/$pluginSection.php";

            $this->plugins[$pluginSection] = new $pluginSection($this);

            ++$loadedPluginCount;
        }

        return $loadedPluginCount;
    }

    public function getPlugins()
    {
        return $this->plugins;
    }

    private function isPluginEligibleForPacket(&$name, &$hostID)
    {
        foreach ($this->pluginvars[$name]['useHosts'] as $host) {
            if ($host == '*' || $host == $hostID) {
                return true;
            }
        }

        return false;
    }

    public function dispatchPacket(&$packet, &$hostID)
    {
        global $PRISM;

        $PRISM->hosts->curHostID = $hostID;

        foreach ($this->plugins as $name => $plugin) {
            // If the packet we are looking at has no callbacks for this packet type don't go to the loop.
            if (!isset($plugin->callbacks[$packet->Type])) {
                continue;
            }

            // If the plugin is not registered on this server, skip this plugin.
            if (!$this->isPluginEligibleForPacket($name, $hostID)) {
                continue;
            }

            foreach ($plugin->callbacks[$packet->Type] as $callback) {
                if (($plugin->$callback($packet)) == PLUGIN_HANDLED) {
                    continue 2; // Skips all of the rest of the plugins who wanted this packet.
                }
            }
        }
    }
}
