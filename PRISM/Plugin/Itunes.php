<?php
/**
 * Itunes.php
 *
 * @category   Plugin
 * @package    PRISM
 * @subpackage Plugin\Itunes
 * @author     Dygear (Mark Tomlin) <Dygear@gmail.com>
 * @author     zenware (Jay Looney) <jay.m.looney@gmail.com>
 * @license    http://opensource.org/licenses/MIT MIT License
 * @link       https://github.com/zenware/PRISM/blob/devel/Plugin/Itunes.php
 */

namespace PRISM\Plugin;

/**
 * In Game iTunes HUD
 *
 * @category   Plugin
 * @package    PRISM
 * @subpackage Plugin\Itunes
 * @author     Dygear (Mark Tomlin) <Dygear@gmail.com>
 * @license    http://opensource.org/licenses/MIT MIT License
 * @link       http://lfsforum.net/forumdisplay.php?f=312
 */
class Itunes extends Plugins
{
    const URL = 'http://lfsforum.net/forumdisplay.php?f=312';
    const NAME = 'iTunes';
    const AUTHOR = 'Mark \'Dygear\' Tomlin';
    const VERSION = PRISM::VERSION;
    const DESCRIPTION = 'In Game iTunes HUD';

    private $_isShowing = false;
    private $_isPlaying = false; // What exactly is this used for?
    private $_buttons = array();

    /**
     * registers commands
     * creates buttons
     * shows info
     */
    public function __construct()
    {
        $this->iTunes = new COM("iTunes.Application") OR $this->iTunes = false;
        $this->registerSayCommand('iTunes', 'iTunes');

        // Buttons
        //Controls
        $this->_buttons['PT'] = new Button(0, 'PreviousTrack', 'iTunes');
        $this->_buttons['PT']->BStyle(ISB_DARK)->T(128)->L(0)->W(16)->H(4)->Text('<<')->registerOnClick($this, 'onPrevTrack');

        $this->_buttons['PP'] = new Button(0, 'PlayPause', 'iTunes');
        $this->_buttons['PP']->BStyle(ISB_DARK)->T(128)->L(16)->W(16)->H(4)->Text('|>')->registerOnClick($this, 'onPlayPause');

        $this->_buttons['NT'] = new Button(0, 'NextTrack', 'iTunes');
        $this->_buttons['NT']->BStyle(ISB_DARK)->T(128)->L(32)->W(16)->H(4)->Text('>>')->registerOnClick($this, 'onNextTrack');

        // Song Info
        $this->_buttons['TM'] = new Button(0, 'Time', 'iTunes');
        $this->_buttons['TM']->BStyle(ISB_DARK)->T(128)->L(48)->W(16)->H(4)->registerOnClick($this, 'onNextTrack');

        $this->_buttons['cA'] = new Button(0, 'currentArtist', 'iTunes');
        $this->_buttons['cA']->BStyle(ISB_DARK)->T(132)->L(0)->W(64)->H(4);

        $this->_buttons['cB'] = new Button(0, 'currentAlbum', 'iTunes');
        $this->_buttons['cB']->BStyle(ISB_DARK)->T(136)->L(0)->W(64)->H(4);

        $this->_buttons['cS'] = new Button(0, 'currentSong', 'iTunes');
        $this->_buttons['cS']->BStyle(ISB_DARK)->T(140)->L(0)->W(64)->H(4);
    }

    /**
     * Is this not a constructor also?
     */
    public function Itunes()
    {
        $this->_isShowing = !$this->_isShowing; // can we not invert the tests instead of the value?
        if ($this->_isShowing === true) {
            ButtonManager::removeButtonsByGroup(0, 'iTunes');
        }

        $this->_buttons['PT']->send();
        $this->_buttons['PP']->send();
        $this->_buttons['NT']->send();
        $this->_buttons['TM']->send();
        $this->onScreenRefresh();

        $this->createTimer('onScreenRefresh', 1, Timer::REPEAT);
    }

    /**
     * Update the displayed data with current info.
     *
     * @return none
     */
    public function onScreenRefresh()
    {
        if ($this->_isShowing === false) {
            return; // Is there some better way to do this?
        }

        $currentTrack =& $this->iTunes->CurrentTrack();
        $this->_buttons['TM']->Text(date('i:s', $this->iTunes->PlayerPosition) . ' / ' . date('i:s', $this->iTunes->CurrentTrack()->Duration))->send();
        $this->_buttons['cA']->Text($currentTrack->Artist)->send();
        $this->_buttons['cB']->Text($currentTrack->Album)->send();
        $this->_buttons['cS']->Text($currentTrack->Name)->send();
    }

    /**
     * Pause the playback.
     *
     * @return none
     */
    public function onPlayPause()
    {
        $this->iTunes->PlayPause();
        $currentTrack =& $this->iTunes->CurrentTrack();
        $this->_buttons['TM']->Text(date('i:s', $this->iTunes->PlayerPosition) . ' / ' . date('i:s', $this->iTunes->CurrentTrack()->Duration))->send();
        $this->_buttons['cA']->Text($currentTrack->Artist)->send();
        $this->_buttons['cB']->Text($currentTrack->Album)->send();
        $this->_buttons['cS']->Text($currentTrack->Name)->send();
    }

    /**
     * Begins playing next track in playlist.
     *
     * @return none
     */
    public function onNextTrack()
    {
        $this->iTunes->NextTrack();
        $currentTrack =& $this->iTunes->CurrentTrack();
        $this->_buttons['TM']->Text(date('i:s', $this->iTunes->PlayerPosition) . ' / ' . date('i:s', $this->iTunes->CurrentTrack()->Duration))->send();
        $this->_buttons['cA']->Text($currentTrack->Artist)->send();
        $this->_buttons['cB']->Text($currentTrack->Album)->send();
        $this->_buttons['cS']->Text($currentTrack->Name)->send();
    }

    /**
     * Begins playing previous track in playlist.
     *
     * @return none
     */
    public function onPrevTrack()
    {
        $this->iTunes->PreviousTrack();
        $this->_buttons['TM']->Text(date('i:s', $this->iTunes->PlayerPosition) . ' / ' . date('i:s', $this->iTunes->CurrentTrack()->Duration))->send();
        $this->_buttons['cA']->Text($currentTrack->Artist)->send();
        $this->_buttons['cB']->Text($currentTrack->Album)->send();
        $this->_buttons['cS']->Text($currentTrack->Name)->send();
    }

    /**
     * Buttons are removed manually.
     *
     * @return none
     */
    public function __deconstruct()
    {
        $this->iTunes = null;
        ButtonManager::removeButtonsByGroup(0, 'iTunes');
    }
}
