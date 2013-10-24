<?php
/**
 * Reorder.php
 *
 * @category   Plugin
 * @package    PRISM
 * @subpackage Plugin\Reorder
 * @author     Dygear (Mark Tomlin) <Dygear@gmail.com>
 * @author     misiek08
 * @author     zenware (Jay Looney) <jay.m.looney@gmail.com>
 * @license    http://opensource.org/licenses/MIT MIT License
 * @link       https://github.com/zenware/PRISM/blob/master/PRISM.php
 */

namespace PRISM\Plugin;

/**
 * Reorders players on the starting grid.
 *
 * @category   Plugin
 * @package    PRISM
 * @subpackage Plugin\Reorder
 * @author     Dygear (Mark Tomlin) <Dygear@gmail.com>
 * @author     misiek08
 * @author     zenware (Jay Looney) <jay.m.looney@gmail.com>
 * @license    http://opensource.org/licenses/MIT MIT License
 * @link       https://github.com/zenware/PRISM/blob/master/PRISM.php
 *
 */
class Reorder extends Plugins
{
    const URL = 'http://lfsforum.net/forumdisplay.php?f=312';
    const NAME = 'Reorder Plugin';
    const AUTHOR = 'Dygear & misiek08';
    const VERSION = PRISM::VERSION;
    const DESCRIPTION = 'Reorder Players on the Starting Grid';

    private $PLID = array();
    private $reorder = false;
    private $IS_REO;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->IS_REO = IS_REO();
        $this->registerSayCommand('prism reo', 'cmdREO', "<PName> ... - Set's the grid for the next race.", ADMIN_VOTE);
        $this->registerPacket('onReorder', ISP_REO);
        $this->registerPacket('onVoteAction', ISP_TINY);
    }

    /**
     *
     */
    public function cmdREO($Msg, $UCID)
    {
        if (($argc = count($argv = str_getcsv($cmd, ' '))) < 2) {
            IS_MTC()->UCID($UCID)->Text('You must give at least one PName.')->Send();
            return PLUGIN_CONTINUE;
        }

        $this->reorder = true;
        $this->PLID = array();

        foreach ($PNames as $PName) {
            $this->PLID[] = $this->getPlayerByPName($PName)->PLID;
        }

        $this->IS_REO->NumP(count($this->PLID))->PLID($this->PLID);

        return PLUGIN_HANDLED;
    }

    /**
     * Send IS_MTC packet
     * Update list
     *
     * @param bool $REO
     * @return bool
     */
    public function onReorder(IS_REO $REO)
    {
        $this->reorder = false; // As we are copying LFS's REO state, we don't need to send this packet on TINY_VTA packet.

        foreach ($REO->PLID as $Pos => $PLID) {
            IS_MTC()->Text(sprintf('Pos: %02d | PLID: %02d | UName: %24s | PName: %24s', $Pos, $PLID, $this->getClientByPLID($PLID)->UName, $this->getPlayerByPLID($PLID)->PName))->Send();
        }

        $this->IS_REO = $REO; // Updates our list.

        return PLUGIN_CONTINUE;
    }

    public function onVoteAction(IS_TINY $TINY)
    {
        if ($this->reorder AND $TINY->SubT == SMALL_VTA) {
            $this->IS_REO->Send();
        }

        return PLUGIN_CONTINUE;
    }
}
