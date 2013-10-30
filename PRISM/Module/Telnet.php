<?php
/**
 * PHPInSimMod - Telnet Module
 * @package PRISM
 * @subpackage Telnet
*/

/*
require_once(ROOTPATH . '/modules/prism_telnet_defines.php');
require_once(ROOTPATH . '/modules/prism_telnet_server.php');
require_once(ROOTPATH . '/modules/prism_telnet_admins.php');
require_once(ROOTPATH . '/modules/prism_telnet_hosts.php');
require_once(ROOTPATH . '/modules/prism_telnet_plugins.php');
*/

namespace PRISM\Module;
use PRISM\Module\Telnet\Server as TelnetServer;
use PRISM\Module\Telnet\Admins;
use PRISM\Module\Telnet\PluginSection as TSPluginSection;
//use PRISM\Module\Telnet\Hosts;

define('TELNET_NOT_LOGGED_IN', 0);
define('TELNET_ASKED_USERNAME', 1);
define('TELNET_ASKED_PASSWORD', 2);
define('TELNET_LOGGED_IN', 3);
define('TS_SECTION_MAIN', 1);

// Standard control keys
define('KEY_IP',                    chr(0x03));         // Interrupt Process (break)
define('KEY_BS',                    chr(0x08));         // backspace
define('KEY_TAB',                   chr(0x09));         // TAB
define('KEY_SHIFTTAB',              chr(0x01).chr(9));  // SHIFT-TAB
define('KEY_ENTER',                 chr(0x0A));         // Enter
define('KEY_ESCAPE',                chr(0x1B));         // escape
define('KEY_DELETE',                chr(0x7F));         // del

// Self defined key codes
define('KEY_CURLEFT',               chr(0x01).chr(0));      // Cursor LEFT
define('KEY_CURRIGHT',              chr(0x01).chr(1));      // Cursor LEFT
define('KEY_CURUP',                 chr(0x01).chr(2));      // Cursor LEFT
define('KEY_CURDOWN',               chr(0x01).chr(3));      // Cursor LEFT
define('KEY_HOME',                  chr(0x01).chr(4));      // Home
define('KEY_END',                   chr(0x01).chr(5));      // End
define('KEY_PAGEUP',                chr(0x01).chr(6));      // Home
define('KEY_PAGEDOWN',              chr(0x01).chr(7));      // End
define('KEY_INSERT',                chr(0x01).chr(8));      // Insert

define('KEY_CURLEFT_CTRL',          chr(0x02).chr(0));      // Cursor LEFT with ctrl
define('KEY_CURRIGHT_CTRL',         chr(0x02).chr(1));      // Cursor LEFT with ctrl
define('KEY_CURUP_CTRL',            chr(0x02).chr(2));      // Cursor LEFT with ctrl
define('KEY_CURDOWN_CTRL',          chr(0x02).chr(3));      // Cursor LEFT with ctrl

define('KEY_F1',                    chr(0x01).chr(11));     // F1
define('KEY_F2',                    chr(0x01).chr(12));     // F2
define('KEY_F3',                    chr(0x01).chr(13));     // F3
define('KEY_F4',                    chr(0x01).chr(14));     // F4
define('KEY_F5',                    chr(0x01).chr(15));     // F5
define('KEY_F6',                    chr(0x01).chr(17));     // F6
define('KEY_F7',                    chr(0x01).chr(18));     // F7
define('KEY_F8',                    chr(0x01).chr(19));     // F8
define('KEY_F9',                    chr(0x01).chr(20));     // F9
define('KEY_F10',                   chr(0x01).chr(21));     // F10
define('KEY_F11',                   chr(0x01).chr(23));     // F11
define('KEY_F12',                   chr(0x01).chr(24));     // F12

// ANSI escape sequences VT100
define('VT100_USG0',                KEY_ESCAPE.'(B');
define('VT100_USG1',                KEY_ESCAPE.')B');
define('VT100_USG0_LINE',           KEY_ESCAPE.'(0');
define('VT100_USG1_LINE',           KEY_ESCAPE.')0');
define('VT100_G0_ALTROM',           KEY_ESCAPE.'(1');
define('VT100_G1_ALTROM',           KEY_ESCAPE.')1');
define('VT100_G0_ALTROM_GFX',       KEY_ESCAPE.'(2');
define('VT100_G1_ALTROM_GFX',       KEY_ESCAPE.')2');

define('VT100_SSHIFT2',             KEY_ESCAPE.'N');
define('VT100_SSHIFT3',             KEY_ESCAPE.'O');

define('VT100_ED2',                 KEY_ESCAPE.'[2J');      // Clear entire screen

define('VT100_CURSORHOME',          KEY_ESCAPE.'[H');       // Move cursor to upper-left corner

define('VT100_STYLE_RESET',         KEY_ESCAPE.'[0m');      // Attribs off
define('VT100_STYLE_BOLD',          KEY_ESCAPE.'[1m');      // bold
define('VT100_STYLE_LOWINTENS',     KEY_ESCAPE.'[2m');      // low intensity
define('VT100_STYLE_UNDERLINE',     KEY_ESCAPE.'[4m');      // underline
define('VT100_STYLE_BLINK',         KEY_ESCAPE.'[5m');      // blink
define('VT100_STYLE_REVERSE',       KEY_ESCAPE.'[7m');      // reverse video
define('VT100_STYLE_INVISIBLE',     KEY_ESCAPE.'[8m');      // invisible text

define('VT100_STYLE_BLACK',         KEY_ESCAPE.'[30m');
define('VT100_STYLE_RED',           KEY_ESCAPE.'[31m');
define('VT100_STYLE_GREEN',         KEY_ESCAPE.'[32m');
define('VT100_STYLE_YELLOW',        KEY_ESCAPE.'[33m');
define('VT100_STYLE_BLUE',          KEY_ESCAPE.'[34m');
define('VT100_STYLE_MAGENTA',       KEY_ESCAPE.'[35m');
define('VT100_STYLE_CYAN',          KEY_ESCAPE.'[36m');
define('VT100_STYLE_WHITE',         KEY_ESCAPE.'[37m');

define('VT100_STYLE_BG_BLACK',      KEY_ESCAPE.'[40m');
define('VT100_STYLE_BG_RED',        KEY_ESCAPE.'[41m');
define('VT100_STYLE_BG_GREEN',      KEY_ESCAPE.'[42m');
define('VT100_STYLE_BG_YELLOW',     KEY_ESCAPE.'[43m');
define('VT100_STYLE_BG_BLUE',       KEY_ESCAPE.'[44m');
define('VT100_STYLE_BG_MAGENTA',    KEY_ESCAPE.'[45m');
define('VT100_STYLE_BG_CYAN',       KEY_ESCAPE.'[46m');
define('VT100_STYLE_BG_WHITE',      KEY_ESCAPE.'[47m');

define('TELNET_CURSOR_HIDE', 1);

/**
 * The PrismTelnet class handles :
 * -the Prism telnet login session
 * -all the information coming from the telnet client (KB input / commands)
 * -what will be drawn on the telnet client's screen
*/
class Telnet extends TelnetServer
{
    // If filled in, the user is logged in (or half-way logging in).
    private $username		= '';

    // The state of the login process.
    private $loginState		= 0;

    // Section vars
    private $curSection		= '';		// holds the name of the currently active section
    private $section		= null;		// holds the actual active section object itself (accounts, hosts, plugins)

    private $menuBar		= null;		// cosmetic menu bar

    private $adminSection	= null;		// handles all account related stuff
    private $hostSection	= null;		// handles all host related stuff
    private $pluginSection	= null;		// handles all plugin related stuff

    public function __construct(&$sock, &$ip, &$port)
    {
        parent::__construct($sock, $ip, $port);

        // Clear screen
        $this->screenClear(true);

        // Send welcome message and ask for username
        $msg = "Welcome to the ".VT100_STYLE_BOLD."Prism v".PHPInSimMod::VERSION.VT100_STYLE_RESET." remote console.\r\n";
        $msg .= "Please login with your Prism account details.\r\n\r\n";
        $msg .= "Username : ";

        $this->writeBuf($msg);
        $this->flush();
        $this->loginState = TELNET_ASKED_USERNAME;

        $this->registerInputCallback($this, 'doLogin', TELNET_MODE_LINEEDIT);
    }

    public function __destruct()
    {
        $this->registerInputCallback(null);
        $this->setCursorProperties(0);

        // Remove all visual objects
        $this->clearObjects(true);

        // Clean up the sections
        if ($this->adminSection) {
            $this->adminSection->__destruct();
            $this->hostSection->__destruct();
            $this->pluginSection->__destruct();
        }

        $this->writeBuf(VT100_STYLE_RESET.VT100_USG0."Goodbye...\r\n");
        $this->flush();
    }

    protected function doLogin($line)
    {
        switch($this->getLoginState()) {
            case TELNET_NOT_LOGGED_IN :
                // Send error notice and ask for username
                $msg .= "\r\nPlease login with your Prism account details.\r\n";
                $msg .= "Username : ";

                $this->write($msg);
                $this->loginState = TELNET_ASKED_USERNAME;

                break;
            case TELNET_ASKED_USERNAME :
                if ($line == '') {
                    $this->write("\r\nUsername : ");
                    break;
                }

                $this->username = $line;
                $this->write("\r\nPassword : ");
                $this->loginState = TELNET_ASKED_PASSWORD;
                $this->setEchoChar('*');

                break;
            case TELNET_ASKED_PASSWORD :
                $this->setEchoChar(null);

                if ($this->verifyLogin($line)) {
                    $this->loginState = TELNET_LOGGED_IN;

                    $this->writeBuf("\r\nLogin successful\r\n");
                    $this->writeBuf("(x or ctrl-c to exit)\r\n");
                    $this->setCursorProperties(TELNET_CURSOR_HIDE);
                    $this->flush();

                    console('Successful telnet login from '.$this->username.' on '.date('r'));

                    // Now setup the screen
                    $this->setupMenu();
                } else {
                    $msg = "\r\nIncorrect login. Please try again.\r\n";
                    $msg .= "Username : ";
                    $this->username = '';
                    $this->write($msg);
                    $this->loginState = TELNET_ASKED_USERNAME;
                }
                break;
        }
    }

    protected function getLoginState()
    {
        return $this->loginState;
    }

    private function verifyLogin(&$password)
    {
        global $PRISM;

        return ($PRISM->admins->isPasswordCorrect($this->username, $password));
    }

    private function setupMenu()
    {
        $this->screenClear();

        // Create section bar (header bar)
        $this->menuBar = new MenuBar($this->getTType());
        $this->add($this->menuBar);

        // Initialise the actual sections as separate objects.
        $this->adminSection = new TSAdminSection($this, $this->getWidth(), $this->getHeight()-3, $this->getTType());
        $this->adminSection->setActive(true);
        $this->add($this->adminSection);
        $this->section = $this->adminSection;
        $this->curSection = 'admins';

        $this->hostSection = new TSHostSection($this, $this->getWidth(), $this->getHeight()-3, $this->getTType());
        $this->hostSection->setActive(true);
        $this->setVisible(false);
        $this->add($this->hostSection);

        $this->pluginSection = new TSPluginSection($this, $this->getWidth(), $this->getHeight()-3, $this->getTType());
        $this->pluginSection->setActive(true);
        $this->setVisible(false);
        $this->add($this->pluginSection);

        $this->registerInputCallback($this, 'handleKey');
        $this->reDraw();
    }

    private function selectSection($section)
    {
        if ($this->curSection == $section) {
            return true;
        }


        // Make the section active
        switch ($section) {
            case 'admins' :
                $this->section->setVisible(false);
                $this->section = $this->adminSection;
                break;

            case 'hosts' :
                $this->section->setVisible(false);
                $this->section = $this->hostSection;
                break;

            case 'plugins' :
                $this->section->setVisible(false);
                $this->section = $this->pluginSection;
                break;

            default :
                return false;
        }

        $this->section->setVisible(true);

        $this->menuBar->selectSection($section);
        $this->curSection = $section;

        return true;
    }

    /**
     * When we are not in line-edit mode (to process a whole line of user-input),
     * we use this handleKey function to process single key presses.
     * These key presses drive the telnet text console application.
     * In other words, the handleKey function is the main() of the telnet console application.
    */
    protected function handleKey($key)
    {
        if (($tl = $this->getObjectById('testline')) === null) {
            $tl = new TSTextArea(1, $this->winSize[1], $this->winSize[0], 1);
            $tl->setId('testline');
            $this->add($tl);
        }

        // Handle section specific keys
        if ($this->section && $this->section->handleKey($key)) {
            $this->redraw();
            return;
        }

        // Default key actions
        switch ($key) {
            case 'A' :
                $this->selectSection('admins');
                break;
            case 'H' :
                $this->selectSection('hosts');
                break;
            case 'P' :
                $this->selectSection('plugins');
                break;
            case 'x' :
                $this->shutdown();
                return;
            case KEY_ENTER :
                $tl->setText('Enter');
                break;
            case KEY_CURLEFT :
                $tl->setText('Cursor left');
                break;
            case KEY_CURRIGHT :
                $tl->setText('Cursor right');
                break;
            case KEY_CURUP :
                $tl->setText('Cursor up');
                break;
            case KEY_CURDOWN :
                $tl->setText('Cursor down');
                break;
            case KEY_CURLEFT_CTRL :
                $tl->setText('CTRL-Cursor left');
                break;
            case KEY_CURRIGHT_CTRL :
                $tl->setText('CTRL-Cursor right');
                break;
            case KEY_CURUP_CTRL :
                $tl->setText('CTRL-Cursor up');
                break;
            case KEY_CURDOWN_CTRL :
                $tl->setText('CTRL-Cursor down');
                break;
            case KEY_HOME :
                $tl->setText('Home key');
                break;
            case KEY_END :
                $tl->setText('End key');
                break;
            case KEY_PAGEUP :
                $tl->setText('Page up');
                break;
            case KEY_PAGEDOWN :
                $tl->setText('Page down');
                break;
            case KEY_INSERT :
                $tl->setText('Insert');
                break;
            case KEY_BS :
                $tl->setText('Backspace');
                break;
            case KEY_TAB :
                $tl->setText('TAB key');
                break;
            case KEY_SHIFTTAB :
                $tl->setText('SHIFT-TAB key');
                break;
            case KEY_DELETE :
                $tl->setText('Delete key');
                break;
            case KEY_ESCAPE :
                $tl->setText('Escape key');
                break;
            case KEY_F1 :
                $tl->setText('F1 key');
                break;
            case KEY_F2 :
                $tl->setText('F2 key');
                break;
            case KEY_F3 :
                $tl->setText('F3 key');
                break;
            case KEY_F4 :
                $tl->setText('F4 key');
                break;
            case KEY_F5 :
                $tl->setText('F5 key');
                break;
            case KEY_F6 :
                $tl->setText('F6 key');
                break;
            case KEY_F7 :
                $tl->setText('F7 key');
                break;
            case KEY_F8 :
                // Toggle ttypes
                $this->setTType($this->getTType() + 1);

                if ($this->getTType() == TELNET_TTYPE_NUM) {
                    $this->setTType(0);
                }

                $this->updateTTypes($this->getTType());
                $tl->setText('Toggling ttype ('.$this->getTType().')');
                break;
            case KEY_F9 :
                $tl->setText('F9 key');
                break;
            case KEY_F10 :
                $tl->setText('F10 key');
                break;
            case KEY_F11 :
                $tl->setText('F11 key');
                break;
            case KEY_F12 :
                $tl->setText('F12 key');
                break;
            default :
                $tl->setText($key.' pressed');
                break;
        }

        $this->redraw();
    }
}
