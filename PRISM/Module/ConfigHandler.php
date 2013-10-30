<?php
/**
 * PHPInSimMod - Config Module
 * @package PRISM
 * @subpackage Config
*/

namespace PRISM\Module;
use PRISM\Module\SectionHandler;

class ConfigHandler extends SectionHandler //use as ConfigHandler
{
    public $cvars	= array
        (
            'prefix'		=> '!',
            'debugMode'		=> PRISM_DEBUG_ALL,
            'logMode'		=> 7,
            'logFileMode'	=> 3,
            'relayIP'		=> 'isrelay.lfs.net',
            'relayPort'		=> 47474,
            'relayPPS'		=> 2,
            'dateFormat'	=> 'M jS Y',
            'timeFormat'	=> 'H:i:s',
            'logFormat'		=> 'm-d-y@H:i:s',
            'logNameFormat'	=> 'Ymd',
            'secToken'		=> 'X-0ZbIY)TN>.@sr}',
        );

    public function __construct()
    {
        $this->iniFile = 'cvars.ini';
    }

    public function init()
    {
        global $PRISM;

        if ($this->loadIniFile($this->cvars, false)) {
            if ($this->cvars['debugMode'] & PRISM_DEBUG_CORE) {
                $PRISM->console('Loaded '.$this->iniFile);
            }
        } else {
            $this->cvars['secToken'] = str_replace(array('"', '\'', ' '), '.', $PRISM->createRandomString(16));

            $PRISM->console('Using cvars defaults.');
            if ($this->createIniFile('PHPInSimMod Configuration Variables', array('prism' => &$this->cvars))) {
                $PRISM->console('Generated config/'.$this->iniFile);
            }
        }

        return true;
    }
}
