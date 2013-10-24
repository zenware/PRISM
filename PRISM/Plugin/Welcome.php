<?php
/**
 * Welcome.php
 *
 * @category   Plugin
 * @package    PRISM
 * @subpackage Plugin\Welcome
 * @author     Dygear (Mark Tomlin) <Dygear@gmail.com>
 * @author     zenware (Jay Looney) <jay.m.looney@gmail.com>
 * @license    http://opensource.org/licenses/MIT MIT License
 * @link       https://github.com/zenware/PRISM/blob/devel/Plugin/Welcome.php
 */

namespace PRISM\Plugin;

/**
 * Welcome messages for clients and Message of the Day
 *
 * @category   Plugin
 * @package    PRISM
 * @subpackage Plugin\Welcome
 * @author     Dygear (Mark Tomlin) <Dygear@gmail.com>
 * @author     ripnet (Tom Young) <ripnet@gmail.com>
 * @author     morpha (Constantin Köpplinger) <morpha@xigmo.net>
 * @author     Victor (Victor van Vlaardingen) <vic@lfs.net>
 * @author     GeForz (Kai Lochbaum)
 * @license    http://opensource.org/licenses/MIT MIT License
 * @link       http://lfsforum.net/forumdisplay.php?f=312
 */
class Welcome extends Plugin
{
    const URL = 'http://lfsforum.net/forumdisplay.php?f=312';
    const NAME = 'Welcome & MOTD';
    const AUTHOR = 'PRISM Dev Team';
    const VERSION = PRISM::VERSION;
    const DESCRIPTION = 'Welcome messages for clients, and Message of the Day (MOTD)';

    /**
     * Welcome Constructor
     * Registers callback functions to packets
     *
     * @see Plugin::registerPacket();
     */
    public function __construct()
    {
        $this->registerPacket('onPrismConnect', ISP_VER);
        $this->registerPacket('onClientConnect', ISP_NCN);
    }

    /**
     * Welcome::onPrismConnect();
     * Sends PRISM version over IS_MSX() packet.
     *
     * @param IS_VER $VER Version?
     *
     * @return none
     */
    public function onPrismConnect(IS_VER $VER)
    {
        IS_MSX()->Msg('PRISM Version ^3'.PRISM::VERSION.'^8 Has Connected.')->Send();
    }

    /**
     * PRISM\Plugin\Welcome::onClientConnect();
     * Some Server side stuff is spawned.
     *
     * @param IS_NCN $NCN I'm not sure what this is.
     *
     * @return none
     */
    public function onClientConnect(IS_NCN $NCN)
    {
        if ($NCN->UCID == 0) {
            return;
        }

        $Title = new Button('poweredBy', Button::$TO_ALL);
        $Title->Text('This server is powered by');
        $Title->registerOnClick($this, 'onPoweredByClick');
        $Title->T(IS_Y_MAX - IS_Y_MIN)->L(IS_X_MIN)->W(IS_X_MAX)->H(8)->send();

        $Msg = new Button('prism', Button::$TO_ALL);
        $Msg->Text('^3PRISM ^8Version ^7'.PRISM::VERSION.'^8.');
        $Msg->T(IS_Y_MAX - IS_Y_MIN + 8)->L(IS_X_MIN)->W(IS_X_MAX)->H(8)->send();
    }

    /**
     * Does something on a click...
     *
     * @param IS_BTC $BTC Some sort of string appended to the text 'Button clicked!'
     *
     * @return none
     */
    public function onPoweredByClick(IS_BTC $BTC)
    {
        echo 'Button clicked! ' . $BTC;
    }
}
