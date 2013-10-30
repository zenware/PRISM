<?php
/**
 * PHPInSimMod - Telnet Module
 * @package PRISM
 * @subpackage Telnet
*/

namespace PRISM\Module\Telnet;
use PRISM\Module\Telnet\Screen\TextArea as TSTextArea;
use PRISM\Module\Telnet\Screen\Container as ScreenContainer;

// Screen object options
define('TS_OPT_ISSELECTABLE', 1);
define('TS_OPT_ISSELECTED', 2);
define('TS_OPT_ISEDITABLE', 4);
define('TS_OPT_HASBACKGROUND', 8);
define('TS_OPT_BOLD', 16);

// Terminal types
define('TELNET_TTYPE_OTHER',    0);
define('TELNET_TTYPE_XTERM',    1);
define('TELNET_TTYPE_ANSI',     2);
define('TELNET_TTYPE_NUM',      3);

/**
 * The TelnetScreen class is the Parent container that holds all visual components
*/
abstract class Screen extends ScreenContainer // Use as TelnetScreen
{
    abstract protected function write($data, $sendQPacket = FALSE);

    protected $winSize				= null;
    protected $modeState			= 0;

    protected $screenBuf			= '';
    protected $cursorProperties		= 0;

    private $postCurPos				= null;

    protected function writeBuf($string)
    {
        $this->screenBuf .= $string;
    }

    protected function writeLine($line, $crlf = true)
    {
        $this->screenBuf .= $line.(($crlf) ? "\r\n" : '');
    }

    protected function writeAt($string, $x, $y)
    {
        $this->screenBuf .= KEY_ESCAPE.'['.$y.';'.$x.'H';
        $this->screenBuf .= $string;
    }

    protected function setWinSize($width, $height)
    {
        $firstTime = ($this->winSize === null) ? true : false;
        $this->winSize = array($width, $height);
        $this->setSize($width, $height);
        if (!$firstTime)
            $this->redraw();
    }

    protected function setCursorProperties($properties = 0)
    {
        $this->cursorProperties = $properties;

        if ($this->getTType() == TELNET_TTYPE_XTERM) {
            if ($this->cursorProperties & TELNET_CURSOR_HIDE)
                $this->screenBuf .= KEY_ESCAPE.'[?25l';
            else
                $this->screenBuf .= KEY_ESCAPE.'[?25h';
        }
    }

    protected function screenClear($goHome = false)
    {
        $this->screenBuf .= VT100_ED2;
        if ($goHome)
            $this->screenBuf .= VT100_CURSORHOME;
    }

    protected function flush()
    {
        if ($this->screenBuf)
            $this->write($this->screenBuf);
        $this->screenBuf = '';
    }

    public function setPostCurPos(array $curPos)
    {
        if (!isset($curPos[0]))
            $this->postCurPos = null;
        else
            $this->postCurPos = $curPos;
    }

    protected function redraw()
    {
        // Clear Screen
        $this->screenBuf .= VT100_ED2;

        // Draw components
        $this->screenBuf .= $this->draw();

        // Park cursor?
        if ($this->postCurPos !== null) {
            $this->screenBuf .= KEY_ESCAPE.'['.$this->postCurPos[1].';'.$this->postCurPos[0].'H';
        } else {
            if (($this->modeState & TELNET_MODE_LINEEDIT) == 0 && $this->getTType() != TELNET_TTYPE_XTERM)
                $this->screenBuf .= KEY_ESCAPE.'[0;'.($this->getWidth() - 1).'H';
//				$this->screenBuf .= KEY_ESCAPE.'['.$this->winSize[1].';'.$this->winSize[0].'H';
        }

        // Flush buffer to client
        $this->flush();
    }

    protected function clearObjects($clearScreen = false)
    {
        $this->screenObjects = array();

        if ($clearScreen) {
            $this->screenClear(true);
        }
    }
}
