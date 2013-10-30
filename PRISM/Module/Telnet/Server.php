<?php
/**
 * PHPInSimMod - Telnet Module
 * @package PRISM
 * @subpackage Telnet
*/

namespace PRISM\Module\Telnet;
use PRISM\Module\Telnet\Screen as TelnetScreen;

//require_once(ROOTPATH . '/modules/prism_telnet_screen.php');

// IAC ACTION OPTION (3 bytes)
define('TELNET_OPT_BINARY',         chr(0x00)); // Binary (RCF 856)
define('TELNET_OPT_ECHO',           chr(0x01)); // (server) Echo (RFC 857)
define('TELNET_OPT_SGA',            chr(0x03)); // Suppres Go Ahead (RFC 858)
define('TELNET_OPT_TTYPE',          chr(0x18)); // Terminal Type (RFC 1091)
define('TELNET_OPT_NAWS',           chr(0x1F)); // Window Size (RFC 1073)
define('TELNET_OPT_TERMINAL_SPEED', chr(0x20)); // Terminal Speed (RFC 1079)
define('TELNET_OPT_TOGGLE_FLOW_CONTROL', chr(0x21));    // flow control (RFC 1372)
define('TELNET_OPT_LINEMODE',       chr(0x22)); // Linemode (RFC 1184)
define('TELNET_OPT_NEW_ENVIRON',    chr(0x27)); // environment variables (RFC 1572)
define('TELNET_OPT_NOP',            chr(0xF1)); // No Operation.

// IAC OPTION (2 bytes)
define('TELNET_OPT_EOF',            chr(0xEC));
define('TELNET_OPT_SUSP',           chr(0xED));
define('TELNET_OPT_ABORT',          chr(0xEE));
define('TELNET_OPT_DM',             chr(0xF2)); // Indicates the position of a Synch event within the data stream. This should always be accompanied by a TCP urgent notification.
define('TELNET_OPT_BRK',            chr(0xF3)); // Break. Indicates that the “break” or “attention” key was hit.
define('TELNET_OPT_IP',             chr(0xF4)); // suspend/abort process.
define('TELNET_OPT_AO',             chr(0xF5)); // process can complete, but send no more output to users terminal.
define('TELNET_OPT_AYT',            chr(0xF6)); // check to see if system is still running.
define('TELNET_OPT_EC',             chr(0xF7)); // delete last character sent typically used to edit keyboard input.
define('TELNET_OPT_EL',             chr(0xF8)); // delete all input in current line.
define('TELNET_OPT_GA',             chr(0xF9)); // Used, under certain circumstances, to tell the other end that it can transmit.

// Suboptions Begin and End (variable byte length options with suboptions)
define('TELNET_OPT_SB',             chr(0xFA)); // Indicates that what follows is subnegotiation of the indicated option.
define('TELNET_OPT_SE',             chr(0xF0)); // End of subnegotiation parameters.

// ACTION bytes
define('TELNET_ACTION_WILL',        chr(0xFB)); // Indicates the desire to begin performing, or confirmation that you are now performing, the indicated option.
define('TELNET_ACTION_WONT',        chr(0xFC)); // Indicates the refusal to perform, or continue performing, the indicated option.
define('TELNET_ACTION_DO',          chr(0xFD)); // Indicates the request that the other party perform, or confirmation that you are expecting theother party to perform, the indicated option.
define('TELNET_ACTION_DONT',        chr(0xFE)); // Indicates the demand that the other party stop performing, or confirmation that you are no longer expecting the other party to perform, the indicated option.

// Command escape char
define('TELNET_IAC',                chr(0xFF)); // Interpret as command (commands begin with this value)

// Linemode sub options
define('LINEMODE_MODE',             chr(0x01));
define('LINEMODE_FORWARDMASK',      chr(0x02));
define('LINEMODE_SLC',              chr(0x03)); // Set Local Characters

// Linemode mode sub option values
define('LINEMODE_MODE_EDIT',        chr(0x01));
define('LINEMODE_MODE_TRAPSIG',     chr(0x02));
define('LINEMODE_MODE_MODE_ACK',    chr(0x04));
define('LINEMODE_MODE_SOFT_TAB',    chr(0x08));
define('LINEMODE_MODE_LIT_ECHO',    chr(0x10));

// Linemode Set Local Characters sub option values
define('LINEMODE_SLC_SYNCH',        chr(1));
define('LINEMODE_SLC_BRK',          chr(2));
define('LINEMODE_SLC_IP',           chr(3));
define('LINEMODE_SLC_AO',           chr(4));
define('LINEMODE_SLC_AYT',          chr(5));
define('LINEMODE_SLC_EOR',          chr(6));
define('LINEMODE_SLC_ABORT',        chr(7));
define('LINEMODE_SLC_EOF',          chr(8));
define('LINEMODE_SLC_SUSP',         chr(9));
define('LINEMODE_SLC_EC',           chr(10));
define('LINEMODE_SLC_EL',           chr(11));
define('LINEMODE_SLC_EW',           chr(12));
define('LINEMODE_SLC_RP',           chr(13));
define('LINEMODE_SLC_LNEXT',        chr(14));
define('LINEMODE_SLC_XON',          chr(15));
define('LINEMODE_SLC_XOFF',         chr(16));
define('LINEMODE_SLC_FORW1',        chr(17));
define('LINEMODE_SLC_FORW2',        chr(18));
define('LINEMODE_SLC_MCL',          chr(19));
define('LINEMODE_SLC_MCR',          chr(20));
define('LINEMODE_SLC_MCWL',         chr(21));
define('LINEMODE_SLC_MCWR',         chr(22));
define('LINEMODE_SLC_MCBOL',        chr(23));
define('LINEMODE_SLC_MCEOL',        chr(24));
define('LINEMODE_SLC_INSRT',        chr(25));
define('LINEMODE_SLC_OVER',         chr(26));
define('LINEMODE_SLC_ECR',          chr(27));
define('LINEMODE_SLC_EWR',          chr(28));
define('LINEMODE_SLC_EBOL',         chr(29));
define('LINEMODE_SLC_EEOL',         chr(30));

define('LINEMODE_SLC_DEFAULT',      chr(3));
define('LINEMODE_SLC_VALUE',        chr(2));
define('LINEMODE_SLC_CANTCHANGE',   chr(1));
define('LINEMODE_SLC_NOSUPPORT',    chr(0));
define('LINEMODE_SLC_LEVELBITS',    chr(3));

define('LINEMODE_SLC_ACK',          chr(128));
define('LINEMODE_SLC_FLUSHIN',      chr(64));
define('LINEMODE_SLC_FLUSHOUT',     chr(32));

// Some telnet edit mode states
define('TELNET_MODE_ECHO', 1);
define('TELNET_MODE_LINEMODE', 2);
define('TELNET_MODE_BINARY', 4);
define('TELNET_MODE_SGA', 8);
define('TELNET_MODE_NAWS', 16);
define('TELNET_MODE_TERMINAL_SPEED', 32);
define('TELNET_MODE_NEW_ENVIRON', 64);

define('TELNET_MODE_INSERT', 1024);
define('TELNET_MODE_LINEEDIT', 2048);

define('TELNET_ECHO_NORMAL', 1);
define('TELNET_ECHO_STAR', 2);
define('TELNET_ECHO_NOTHING', 3);

/**
 * The TelnetServer class does all connection handling and terminal negotiations and input handling.
 * Any telnet input is then passed to the registered callback function.
*/
class Server extends TelnetScreen;
{
    private $socket			= null;
    private $ip				= '';
    private $port			= 0;

    private $lineBuffer		= array();
    private $lineBufferPtr	= 0;
    private $inputBuffer	= '';
    private $inputBufferLen	= 0;
    private $inputBufferMaxLen	= 23;

    // send queue used for backlog, in case we can't send a reply in one go
    private $sendQ			= '';
    private $sendQLen		= 0;

    private $sendWindow		= STREAM_WRITE_BYTES;	// dynamic window size

    private $lastActivity	= 0;
    private $mustClose		= false;

    // Editing related
    private $echoChar		= null;
    private $inputCallback	= null;

    private $charMap		= array();

    public function __construct(&$sock, &$ip, &$port)
    {
        $this->socket		= $sock;
        $this->ip			= $ip;
        $this->port			= $port;

        $this->lastActivity	= time();

        // Start terminal state negotiation
        $this->setTelnetOption(TELNET_ACTION_DO, TELNET_OPT_BINARY);
        $this->setTelnetOption(TELNET_ACTION_WILL, TELNET_OPT_ECHO);
        $this->setTelnetOption(TELNET_ACTION_DO, TELNET_OPT_SGA);
        $this->setTelnetOption(TELNET_ACTION_DO, TELNET_OPT_LINEMODE);
        $this->setTelnetOption(TELNET_ACTION_DO, TELNET_OPT_NAWS);
        $this->setTelnetOption(TELNET_ACTION_DO, TELNET_OPT_TTYPE);

        $this->modeState |= TELNET_MODE_INSERT;
    }

    public function __destruct()
    {
        if ($this->sendQLen > 0)
            $this->sendQReset();

        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
    }

    public function &getSocket()
    {
        return $this->socket;
    }

    public function &getRemoteIP()
    {
        return $this->ip;
    }

    public function &getRemotePort()
    {
        return $this->port;
    }

    public function &getLastActivity()
    {
        return $this->lastActivity;
    }

    public function getMustClose()
    {
        return $this->mustClose;
    }

    /**
     * Sets which character should be echoed when in server echo mode
     * $echoChar = null			- echo what the user types
     * $echoChar = ''			- echo an empty char == echo nothing at all
     * $echoChar = '<somechar>'	- echo <somechar>
    */
    protected function setEchoChar($echoChar)
    {
        $this->echoChar = $echoChar;
    }

    /*
     * $func	  = function that will handle the user's keyboard input
     * $editMode  = either 0 or anything else (TELNET_MODE_LINEEDIT)
     * 				This indicates where the function expects a single char or a whole line
    */
    public function registerInputCallback($class, $func = null, $editMode = 0)
    {
        if (!$class || !$func) {
            $this->inputCallback = null;
            $editMode = 0;
        } else {
            $this->inputCallback = array($class, $func);
//			console('SETTING CALLBACK FUNCTION : '.$func);
        }

        if ($editMode == 0) {
            $this->modeState &= ~TELNET_MODE_LINEEDIT;
            $this->setCursorProperties(TELNET_CURSOR_HIDE);
        } else {
            $this->modeState |= TELNET_MODE_LINEEDIT;
            $this->setCursorProperties(0);
        }
    }

    protected function shutdown()
    {
        $this->mustClose = true;
        $this->registerInputCallback(null);
    }

    private function setTelnetOption($action, $option)
    {
        $this->write(TELNET_IAC.$action.$option);
    }

    public function read(&$data)
    {
        $this->lastActivity	= time();
        return fread($this->socket, STREAM_READ_BYTES);
    }

    public function addInputToBuffer(&$raw)
    {
//		for ($a=0; $a<strlen($raw); $a++)
//			printf('%02x', ord($raw[$a]));
////			printf('%02x', ord($this->translateClientChar($raw[$a])));
//		echo "\n";

        // Add raw input to buffer
        $this->inputBuffer .= $raw;
        $this->inputBufferLen += strlen($raw);
    }

    public function processInput()
    {
        // Here we first check if a telnet command came in.
        // Otherwise we just pass the input to the window handler
        for ($a=0; $a<$this->inputBufferLen; $a++) {
            // Check if next bytes in the buffer is a command
            if ($this->inputBuffer[$a] == TELNET_IAC) {
                $startIndex = $a;
                $a++;
                switch ($this->inputBuffer[$a]) {
                    // IAC ACTION OPTION (3 bytes)
                    case TELNET_ACTION_WILL :
                        switch($this->inputBuffer[$a+1]) {
                            case TELNET_OPT_BINARY :
                                //console('client WILL BINARY');
                                $this->modeState |= TELNET_MODE_BINARY;
                                break;
                            case TELNET_OPT_SGA :
                                //console('client WILL SGA');
                                $this->modeState |= TELNET_MODE_SGA;
                                break;
                            case TELNET_OPT_LINEMODE :
                                //console('client WILL Linemode');
                                $this->modeState |= TELNET_MODE_LINEMODE;
                                break;
                            case TELNET_OPT_NAWS :
                                //console('client WILL NAWS');
                                $this->modeState |= TELNET_MODE_NAWS;
                                break;
                            case TELNET_OPT_TERMINAL_SPEED :
                                //console('client WILL terminal speed');
                                $this->modeState |= TELNET_MODE_TERMINAL_SPEED;
                                $this->setTelnetOption(TELNET_ACTION_DONT, TELNET_OPT_TERMINAL_SPEED);
                                break;
                            case TELNET_OPT_TTYPE :
                                //console('client WILL TTYPE');
                                $this->write(TELNET_IAC.TELNET_OPT_SB.TELNET_OPT_TTYPE.chr(1).TELNET_IAC.TELNET_OPT_SE);
                                //$this->modeState |= TELNET_MODE_NAWS;
                                break;
                            case TELNET_OPT_NEW_ENVIRON :
                                //console('client WILL NEW-ENVIRON');
                                $this->modeState |= TELNET_MODE_NEW_ENVIRON;
                                $this->setTelnetOption(TELNET_ACTION_DO, TELNET_OPT_NEW_ENVIRON);
                                $this->write(TELNET_IAC.TELNET_OPT_SB.TELNET_OPT_NEW_ENVIRON.chr(1).TELNET_IAC.TELNET_OPT_SE);
                                break;
                        }
                        $a++;
                        break;

                    case TELNET_ACTION_WONT :
                        switch($this->inputBuffer[$a+1]) {
                            case TELNET_OPT_BINARY :
                                //console('client WON\'T BINERY');
                                $this->modeState &= ~TELNET_MODE_BINARY;
                                break;
                            case TELNET_OPT_SGA :
                                //console('client WON\'T SGA');
                                $this->modeState &= ~TELNET_MODE_SGA;
                                break;
                            case TELNET_OPT_LINEMODE :
                                //console('client WON\'T Linemode');
                                $this->modeState &= ~TELNET_MODE_LINEMODE;
                                break;
                            case TELNET_OPT_NAWS :
                                //console('client WON\'T NAWS');
                                $this->modeState &= ~TELNET_MODE_NAWS;
                                break;
                            case TELNET_OPT_TERMINAL_SPEED :
                                //console('client WON\'T terminal speed');
                                $this->modeState &= ~TELNET_MODE_TERMINAL_SPEED;
                                break;
                            case TELNET_OPT_TTYPE :
                                //console('client WON\'T TTYPE');
                                //$this->modeState &= ~TELNET_MODE_NAWS;
                                break;
                            case TELNET_OPT_NEW_ENVIRON :
                                //console('client WON\'T NEW-ENVIRON');
                                $this->modeState |= TELNET_MODE_NEW_ENVIRON;
                                break;
                        }
                        $a++;
                        break;

                    case TELNET_ACTION_DO :
                        switch($this->inputBuffer[$a+1]) {
                            case TELNET_OPT_ECHO :
                                //console('Server DO echo');
                                $this->modeState |= TELNET_MODE_ECHO;
                                break;
                            case TELNET_OPT_TTYPE :
                                //console('Server DO ttype');
                                //$this->modeState |= TELNET_MODE_ECHO;
                                break;
                        }
                        $a++;
                        break;

                    case TELNET_ACTION_DONT :
                        switch($this->inputBuffer[$a+1]) {
                            case TELNET_OPT_ECHO :
                                //console('Server DONT echo');
                                $this->modeState &= ~TELNET_MODE_ECHO;
                                break;
                            case TELNET_OPT_TTYPE :
                                //console('Server DONT ttype');
                                //$this->modeState &= ~TELNET_MODE_ECHO;
                                break;
                        }
                        $a++;
                        break;

                    // AIC OPTION (2 bytes)
                    case TELNET_OPT_NOP :
                        break;

                    case TELNET_OPT_DM :
                        break;

                    case TELNET_OPT_BRK :
                        break;

                    case TELNET_OPT_IP :
                        $this->shutdown();
                        return false;

                    case TELNET_OPT_AO :
                        break;

                    case TELNET_OPT_AYT :
                        break;

                    case TELNET_OPT_EC :
                        break;

                    case TELNET_OPT_EL :
                        break;

                    case TELNET_OPT_GA :
                        break;

                    case TELNET_OPT_EOF :
                        break;

                    case TELNET_OPT_SUSP :
                        break;

                    case TELNET_OPT_ABORT :
                        break;

                    // Suboptions (variable length)
                    case TELNET_OPT_SB :
                        // Find the next IAC SE
                        if (($pos = strpos($this->inputBuffer, TELNET_IAC.TELNET_OPT_SE, $a)) === false) {
                            return true;		// we need more data.
                        }

                        $a++;
                        $dist = $pos - $a;
                        $subVars = substr($this->inputBuffer, $a, $dist);
                        // Detect the command type
                        switch ($subVars[0]) {
                            case TELNET_OPT_LINEMODE :
                                switch ($subVars[1]) {
                                    case LINEMODE_MODE :
                                        //console('SB LINEMODE MODE sub command');
                                        break;

                                    case LINEMODE_FORWARDMASK :
                                        //console('SB LINEMODE FORWARDMASK sub command');
                                        break;

                                    case LINEMODE_SLC :
                                        //console('SB LINEMODE SLC sub command ('.strlen($subVars).')');
                                        $this->writeCharMap(substr($subVars, 2));
                                        break;
                                }
                                break;
                            case TELNET_OPT_NAWS :
                                //console('SB NAWS sub command ('.strlen($subVars).')');
                                $this->unescapeIAC($subVars);
                                $screenInfo = unpack('Ctype/nwidth/nheight', $subVars);
                                $this->setWinSize($screenInfo['width'], $screenInfo['height']);
                                break;
                            case TELNET_OPT_TTYPE :
                                $this->unescapeIAC($subVars);
                                $ttype = substr($subVars, 2);
                                if (stripos($ttype, 'xterm') !== false)
                                    $this->setTType(TELNET_TTYPE_XTERM);
                                else if (stripos($ttype, 'ansi') !== false)
                                    $this->setTType(TELNET_TTYPE_ANSI);
                                else
                                    $this->setTType(TELNET_TTYPE_OTHER);

                                //console('SB TTYPE sub command ('.$this->getTType().')');
                                break;
                            case TELNET_OPT_NEW_ENVIRON :
                                $this->unescapeIAC($subVars);

                                switch(ord($subVars[1])) {
                                    case 0 :		// IS
                                        $values = substr($subVars, 2);
                                        console('SB NEW_ENVIRON sub IS command ('.strlen($values).')');
                                        break;
                                    case 1 :		// SEND
                                        break;
                                    case 2 :		// INFO
                                        $values = substr($subVars, 2);
                                        console('SB NEW_ENVIRON sub INFO command ('.strlen($values).')');
                                        break;
                                }

                                //console('SB NEW_ENVIRON sub command ('.strlen($subVars).')');
                                break;
                        }
                        $a += $dist + 1;
                        break;

                    case TELNET_OPT_SE :
                        // Hmm not possible?
                        break;

                    // Command escape char
                    case TELNET_IAC :			// Escaped AIC - treat as single 0xFF; send straight to linebuffer
                        $this->charToLineBuffer($this->inputBuffer[$a]);
                        break;

                    default :
                        console('UNKNOWN TELNET COMMAND ('.ord($this->inputBuffer[$a]).')');
                        break;

                }

                // We have processed a full command - prune it from the buffer
                if ($startIndex == 0) {
                    $this->inputBuffer = substr($this->inputBuffer, $a + 1);
                    $this->inputBufferLen = strlen($this->inputBuffer);
                    $a = -1;
                } else {
                    $this->inputBuffer = substr($this->inputBuffer, 0, $startIndex).substr($this->inputBuffer, $a + 1);
                    $this->inputBufferLen = strlen($this->inputBuffer);
                }
                //console('command');
            } else {
                // Translate char (eg, 7f -> 08)
                $char = $this->translateClientChar($this->inputBuffer[$a]);

                // Check char for special meaning
                $special = false;
                if ($this->modeState & TELNET_MODE_LINEEDIT) {
                    // LINE-EDIT PROCESSING
                    switch ($char) {
                        case KEY_IP :
                            $special = true;
                            $this->shutdown();
                            return false;

                        case KEY_BS :
                            $special = true;

                            // See if there are any characters to (backwards) delete at all
                            if ($this->lineBufferPtr > 0) {
                                $this->lineBufferPtr--;
                                array_splice($this->lineBuffer, $this->lineBufferPtr, 1);

                                // Update the client
                                $rewrite = '';
                                $x = $this->lineBufferPtr;
                                while (isset($this->lineBuffer[$x])) {
                                    if ($this->echoChar !== null)
                                        $rewrite .= $this->echoChar;
                                    else
                                        $rewrite .= $this->lineBuffer[$x];
                                    $x++;
                                }
                                $cursorBack = KEY_ESCAPE.'['.(strlen($rewrite)+1).'D';
                                $this->write(KEY_ESCAPE.'[D'.$rewrite.' '.$cursorBack);
                            }
                            break;

                        case KEY_TAB :
                            $special = true;
                            $this->handleKey(KEY_TAB);
                            break;

                        case KEY_DELETE :
                            $special = true;

                            if ($this->getTType() == TELNET_TTYPE_XTERM &&
                                ($this->modeState & TELNET_MODE_LINEMODE) == 0)
                            {
                                // BACKSPACE
                                if ($this->lineBufferPtr > 0) {
                                    $this->lineBufferPtr--;
                                    array_splice($this->lineBuffer, $this->lineBufferPtr, 1);

                                    // Update the client
                                    $rewrite = '';
                                    $x = $this->lineBufferPtr;
                                    while (isset($this->lineBuffer[$x])) {
                                        if ($this->echoChar !== null)
                                            $rewrite .= $this->echoChar;
                                        else
                                            $rewrite .= $this->lineBuffer[$x];
                                        $x++;
                                    }
                                    $cursorBack = KEY_ESCAPE.'['.(strlen($rewrite)+1).'D';
                                    $this->write(KEY_ESCAPE.'[D'.$rewrite.' '.$cursorBack);
                                }
                            } else {
                                // DELETE
                                // See if we're not at the end of the line buffer
                                if (isset($this->lineBuffer[$this->lineBufferPtr])) {
                                    array_splice($this->lineBuffer, $this->lineBufferPtr, 1);

                                    // Update the client
                                    $rewrite = '';
                                    $x = $this->lineBufferPtr;
                                    while (isset($this->lineBuffer[$x])) {
                                        if ($this->echoChar !== null)
                                            $rewrite .= $this->echoChar;
                                        else
                                            $rewrite .= $this->lineBuffer[$x];
                                        $x++;
                                    }
                                    $cursorBack = KEY_ESCAPE.'['.(strlen($rewrite)+1).'D';
                                    $this->write($rewrite.' '.$cursorBack);
                                }
                            }

                            break;

                        case KEY_ESCAPE :
                            // Always skip at least escape char from lineBuffer.
                            // Below we further adjust the $a pointer where needed.
                            $special = true;

                            // Look ahead in inputBuffer to detect escape sequence
                            if (!isset($this->inputBuffer[$a+1]) ||
                                ($this->inputBuffer[$a+1] != '[' && $this->inputBuffer[$a+1] != 'O'))
                            {
                                $this->handleKey(KEY_ESCAPE);
                                break;
                            }

                            $input = substr($this->inputBuffer, $a);
                            $matches = array();
                            if (preg_match('/^('.KEY_ESCAPE.'\[(\d?)D).*$/', $input, $matches)) {
                                // CURSOR LEFT
                                if ($this->lineBufferPtr > 0) {
                                    $this->write($matches[1]);
                                    $a += strlen($matches[1]) - 1;
                                    $this->lineBufferPtr -= ((int) $matches[2] > 1) ? (int) $matches[2] : 1;
                                }
                            } elseif (preg_match('/^('.KEY_ESCAPE.'\[(\d?)C).*$/', $input, $matches)) {
                                // CURSOR RIGHT
                                if (isset($this->lineBuffer[$this->lineBufferPtr])) {
                                    $this->write($matches[1]);
                                    $a += strlen($matches[1]) - 1;
                                    $this->lineBufferPtr += ((int) $matches[2] > 1) ? (int) $matches[2] : 1;
                                }
                            } elseif (preg_match('/^('.KEY_ESCAPE.'\[(\d?)A).*$/', $input, $matches)) {
                                // CURSOR UP
                                $this->handleKey(KEY_CURUP);
                                //$this->write($matches[1]);
                            } elseif (preg_match('/^('.KEY_ESCAPE.'\[(\d?)B).*$/', $input, $matches)) {
                                // CURSOR DOWN
                                $this->handleKey(KEY_CURDOWN);
                                //$this->write($matches[1]);
                            }

                            // CTRL-Arrow keys
                            else if (preg_match('/^('.KEY_ESCAPE.'\O(\d?)D).*$/', $input, $matches)) {
                                // CURSOR LEFT CTRL
                                //$char = KEY_CURLEFT_CTRL;
                            } elseif (preg_match('/^('.KEY_ESCAPE.'\O(\d?)C).*$/', $input, $matches)) {
                                // CURSOR RIGHT CTRL
                                //$char = KEY_CURRIGHT_CTRL;
                            } elseif (preg_match('/^('.KEY_ESCAPE.'\O(\d?)A).*$/', $input, $matches)) {
                                // CURSOR UP CTRL
                                //$char = KEY_CURUP_CTRL;
                            } elseif (preg_match('/^('.KEY_ESCAPE.'\O(\d?)B).*$/', $input, $matches)) {
                                // CURSOR DOWN CTRL
                                //$char = KEY_CURDOWN_CTRL;
                            }

                            // Other keys
                            else if (preg_match('/^('.KEY_ESCAPE.'\[3~).*$/', $input, $matches)) {
                                // Alternate DEL keycode
                                // See if we're not at the end of the line buffer
                                if (isset($this->lineBuffer[$this->lineBufferPtr])) {
                                    array_splice($this->lineBuffer, $this->lineBufferPtr, 1);

                                    // Update the client
                                    $rewrite = '';
                                    $x = $this->lineBufferPtr;
                                    while (isset($this->lineBuffer[$x])) {
                                        if ($this->echoChar !== null)
                                            $rewrite .= $this->echoChar;
                                        else
                                            $rewrite .= $this->lineBuffer[$x];
                                        $x++;
                                    }
                                    $cursorBack = KEY_ESCAPE.'['.(strlen($rewrite)+1).'D';
                                    $this->write($rewrite.' '.$cursorBack);
                                }
                            } elseif (preg_match('/^('.KEY_ESCAPE.'\[2~).*$/', $input, $matches)) {
                                // INSERT
                                $this->modeState ^= TELNET_MODE_INSERT;
                            } elseif (preg_match('/^('.KEY_ESCAPE.'\[1~).*$/', $input, $matches)) {
                                // HOME
                                // Move cursor to start of edit-line
                                $diff = $this->lineBufferPtr;
                                $this->lineBufferPtr = 0;
                                $this->write(KEY_ESCAPE.'['.$diff.'D');
                            } elseif (preg_match('/^('.KEY_ESCAPE.'\[4~).*$/', $input, $matches)) {
                                // END
                                // Move cursor to end of edit-line
                                $bufLen = count($this->lineBuffer);
                                $diff = $bufLen - $this->lineBufferPtr;
                                $this->lineBufferPtr = $bufLen;
                                $this->write(KEY_ESCAPE.'['.$diff.'C');
                            } elseif (preg_match('/^('.KEY_ESCAPE.'\[Z).*$/', $input, $matches)) {
                                // SHIFT-TAB
                                $this->handleKey(KEY_SHIFTTAB);
                            }

                            // Move inputBuffer pointer ahead to cover multibyte char?
                            if (count($matches) > 1)
                                $a += strlen($matches[1]) - 1;

                            break;
                    }

                    // Regular characers.
                    if ($special)
                        continue;

                    // We must detect the Enter key here
                    $enterChar = $this->isEnter($a);

                    if ($this->modeState & TELNET_MODE_LINEEDIT) {
                        // Line processing
                        if ($enterChar === null) {
                            // Store char in linfe buffer
                            $this->charToLineBuffer($this->inputBuffer[$a]);
                        } else {
                            // Detect whole lines when Enter encountered
                            $this->charToLineBuffer($enterChar, true);
                            do {
                                $line = $this->getLine();
                                if ($line === false)
                                    break;

                                // Send line to the current input callback function (if there is one)
                                $method = $this->inputCallback[1];
                                $this->inputCallback[0]->$method($line);
                            } while(true);
                        }
                    }
                } else {
                    // SINGLE KEY PROCESSING
                    switch ($char) {
                        case KEY_IP :
                            $special = true;
                            $this->shutdown();
                            return false;

                        case KEY_BS :
                        case KEY_TAB :
                        case KEY_DELETE :
                            break;

                        case KEY_ESCAPE :

                            // Look ahead in inputBuffer to detect escape sequence
                            if (!isset($this->inputBuffer[$a+1]) ||
                                ($this->inputBuffer[$a+1] != '[' && $this->inputBuffer[$a+1] != 'O'))
                                break;

                            $input = substr($this->inputBuffer, $a);
                            $matches = array();

                            // Arrow keys
                            if (preg_match('/^('.KEY_ESCAPE.'\[(\d?)D).*$/', $input, $matches)) {
                                // CURSOR LEFT
                                $char = KEY_CURLEFT;
                            } elseif (preg_match('/^('.KEY_ESCAPE.'\[(\d?)C).*$/', $input, $matches)) {
                                // CURSOR RIGHT
                                $char = KEY_CURRIGHT;
                            } elseif (preg_match('/^('.KEY_ESCAPE.'\[(\d?)A).*$/', $input, $matches)) {
                                // CURSOR UP
                                $char = KEY_CURUP;
                            } elseif (preg_match('/^('.KEY_ESCAPE.'\[(\d?)B).*$/', $input, $matches)) {
                                // CURSOR DOWN
                                $char = KEY_CURDOWN;
                            }

                            // CTRL-Arrow keys
                            else if (preg_match('/^('.KEY_ESCAPE.'\O(\d?)D).*$/', $input, $matches)) {
                                // CURSOR LEFT
                                $char = KEY_CURLEFT_CTRL;
                            } elseif (preg_match('/^('.KEY_ESCAPE.'\O(\d?)C).*$/', $input, $matches)) {
                                // CURSOR RIGHT
                                $char = KEY_CURRIGHT_CTRL;
                            } elseif (preg_match('/^('.KEY_ESCAPE.'\O(\d?)A).*$/', $input, $matches)) {
                                // CURSOR UP
                                $char = KEY_CURUP_CTRL;
                            } elseif (preg_match('/^('.KEY_ESCAPE.'\O(\d?)B).*$/', $input, $matches)) {
                                // CURSOR DOWN
                                $char = KEY_CURDOWN_CTRL;
                            }

                            // Other special keys
                            else if (preg_match('/^('.KEY_ESCAPE.'\[3~).*$/', $input, $matches)) {
                                // Alternate DEL keycode
                                $char = KEY_DELETE;
                            } elseif (preg_match('/^('.KEY_ESCAPE.'\[2~).*$/', $input, $matches)) {
                                // INSERT
                                $char = KEY_INSERT;
                            } elseif (preg_match('/^('.KEY_ESCAPE.'\[1~).*$/', $input, $matches)) {
                                // HOME
                                $char = KEY_HOME;
                            } elseif (preg_match('/^('.KEY_ESCAPE.'\[4~).*$/', $input, $matches)) {
                                // END
                                $char = KEY_END;
                            } elseif (preg_match('/^('.KEY_ESCAPE.'\[5~).*$/', $input, $matches)) {
                                // PgUp
                                $char = KEY_PAGEUP;
                            } elseif (preg_match('/^('.KEY_ESCAPE.'\[6~).*$/', $input, $matches)) {
                                // PgDn
                                $char = KEY_PAGEDOWN;
                            } elseif (preg_match('/^('.KEY_ESCAPE.'OP).*$/', $input, $matches)) {
                                // F1 (windows)
                                $char = KEY_F1;
                            } elseif (preg_match('/^('.KEY_ESCAPE.'OQ).*$/', $input, $matches)) {
                                // F2 (windows)
                                $char = KEY_F2;
                            } elseif (preg_match('/^('.KEY_ESCAPE.'OR).*$/', $input, $matches)) {
                                // F3 (windows)
                                $char = KEY_F3;
                            } elseif (preg_match('/^('.KEY_ESCAPE.'OS).*$/', $input, $matches)) {
                                // F4 (windows)
                                $char = KEY_F4;
                            } elseif (preg_match('/^('.KEY_ESCAPE.'\[([0-9]{2})~).*$/', $input, $matches) &&
                                    $matches[2] > 10 && $matches[2] < 25 && $matches[2] != 16 && $matches[2] != 22)
                            {
                                // Fxx
                                $char = chr(1).chr($matches[2]);
                            } elseif (preg_match('/^('.KEY_ESCAPE.'\[Z).*$/', $input, $matches)) {
                                // SHIFT-TAB
                                $char = KEY_SHIFTTAB;
                            } else {
                                console(substr($input, 1));
                            }

                            // Move inputBuffer pointer ahead to cover multibyte char?
                            if (count($matches) > 1)
                                $a += strlen($matches[1]) - 1;

                            break;
                    }

                    if ($special)
                        continue;

                    // Single key processing (if there is a callback at all)
                    if ($this->inputCallback[0]) {
                        if ($this->isEnter($a) !== null)
                            $char = KEY_ENTER;

                        $method = $this->inputCallback[1];
                        $this->inputCallback[0]->$method($char);
                    }
                }
            }
        }

        $this->inputBuffer = substr($this->inputBuffer, $a + 1);
        $this->inputBufferLen = strlen($this->inputBuffer);

        return true;
    }

    // Get a whole line from input
    public function getLine($forceFlush = false)
    {
        // Detect carriage return / line feed / whatever you want to call it
        $count = count($this->lineBuffer);
        if (!$count)
            return false;

        $line = '';
        $haveLine = false;
        for ($a=0; $a<$count; $a++) {
            if ($this->modeState & TELNET_MODE_LINEMODE) {
                if ($this->lineBuffer[$a] == "\r") {
                    $haveLine = true;
                    break;				// break out of the main char by char loop
                }
            } else {
                if (isset($this->lineBuffer[$a+1]) &&
                    $this->lineBuffer[$a].$this->lineBuffer[$a+1] == "\r\n")
                {
                    $a++;
                    $haveLine = true;
                    break;				// break out of the main char by char loop
                }
            }
            $line .= $this->lineBuffer[$a];
        }

        if ($haveLine || $forceFlush) {
            // Send return to client if in echo mode (and later on, if in simple mode)
//			if ($this->modeState & TELNET_MODE_ECHO)
//				$this->write("\r\n");

            // Splice line out of line buffer
            array_splice($this->lineBuffer, 0, $a+1);

            $this->lineBuffer = array();
            $this->lineBufferPtr = 0;
            return $line;
        }

        return false;
    }

    private function isEnter(&$a)
    {
        if ($this->modeState & TELNET_MODE_LINEMODE) {
            if ($this->inputBuffer[$a] == "\r")
                return "\r";
        } else {
            if ($this->inputBuffer[$a] == "\r" && !isset($this->inputBuffer[$a+1])) {
                return "\r\n";
            } elseif (isset($this->inputBuffer[$a+1]) &&
                $this->inputBuffer[$a].$this->inputBuffer[$a+1] == "\r\n")
            {
                $a++;
                return "\r\n";
            }
        }
        return null;
    }

    public function setLineBuffer($chars)
    {
        $this->lineBuffer = array();
        $this->lineBufferPtr = 0;
        for ($a=0; $a<strlen($chars); $a++)
            $this->lineBuffer[$this->lineBufferPtr++] = $chars[$a];
    }

    public function setInputBufferMaxLen($maxLength)
    {
        $this->inputBufferMaxLen = (int) $maxLength;
        if ($this->inputBufferMaxLen < 1)
            $this->inputBufferMaxLen = 1;
    }

    private function charToLineBuffer($char, $isEnter = false)
    {
        // If buffer 'full', just return and ignore the new char
        if (count($this->lineBuffer) == $this->inputBufferMaxLen)
            return;

        // Add the new char
        if ($isEnter) {
            for ($a=0; $a<strlen($char); $a++)
                $this->lineBuffer[] = $char[$a];
        } elseif ($this->modeState & TELNET_MODE_INSERT) {
            for ($a=0; $a<strlen($char); $a++)
                array_splice($this->lineBuffer, $this->lineBufferPtr++, 0, array($char[$a]));
        } else {
            for ($a=0; $a<strlen($char); $a++)
                $this->lineBuffer[$this->lineBufferPtr++] = $char[$a];
        }

        // Must we update the client?
        if ($this->modeState & TELNET_MODE_ECHO) {
            if ($char == KEY_TAB || ($char = filter_var($char, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH)) != '') {
                $rewrite = $cursorBack = '';

                // Are we in insert mode and do we have to move any chars?
                if ($this->modeState & TELNET_MODE_INSERT && isset($this->lineBuffer[$this->lineBufferPtr])) {
                    // Write the remaining chars and return cursor to original pos
                    $x = $this->lineBufferPtr;
                    while (isset($this->lineBuffer[$x]))
                        $rewrite .= $this->lineBuffer[$x++];
                    $cursorBack = KEY_ESCAPE.'['.(strlen($rewrite)).'D';
                }

                if ($this->echoChar !== null)
                    $this->write($this->echoChar.$rewrite.$cursorBack);
                else
                    $this->write($char.$rewrite.$cursorBack);
            }
        }
    }

    private function translateClientChar($char)
    {
        foreach ($this->charMap as $func => $data) {
            if ($data[0] == $char) {
                $tr = $this->getFunctionChar($func);
                if ($tr)
                    return $tr;
            }
        }

        return $char;
    }

    private function translateServerChar($char)
    {
        $this->charMap;
    }

    private function writeCharMap($mapData)
    {
        // Unescape IACIAC
        $this->unescapeIAC($mapData);

        // We must have a number of octect triplets
        $len = strlen($mapData);
        if (($len % 3) != 0)
            return false;

        $a = 0;
        $this->charMap = array();
        while ($a<$len) {
            $func		= $mapData[$a++];
            $options	= $mapData[$a++];
            $ascii		= $mapData[$a++];

            //console(printf());
            $this->charMap[$func] = array($ascii, $options);
        }

        return true;
    }

    private function unescapeIAC(&$data)
    {
        $new = '';
        for ($a=0; $a<strlen($data); $a++) {
            if ($data[$a] == TELNET_IAC &&
                isset($data[$a+1]) &&
                $data[$a+1] == TELNET_IAC)
            {
                continue;
            }
            $new .= $data[$a];
        }
        $data = $new;
    }

    // Get the default ascii character that belongs to a certain SLC function
    private function getFunctionChar($func)
    {
        switch ($func) {
            case LINEMODE_SLC_SYNCH :
                break;

            case LINEMODE_SLC_BRK :
                break;

            case LINEMODE_SLC_IP :
                return KEY_IP;			// ctrl-c

            case LINEMODE_SLC_AO :
                break;

            case LINEMODE_SLC_AYT :
                break;

            case LINEMODE_SLC_EOR :
                break;

            case LINEMODE_SLC_ABORT :
                break;

            case LINEMODE_SLC_EOF :
                break;

            case LINEMODE_SLC_SUSP :
                break;

            case LINEMODE_SLC_EC :
                return KEY_BS;			// backspace

            case LINEMODE_SLC_EL :
                break;

            case LINEMODE_SLC_EW :
                break;

            case LINEMODE_SLC_RP :
                break;

            case LINEMODE_SLC_LNEXT :
                break;

            case LINEMODE_SLC_XON :
                break;

            case LINEMODE_SLC_XOFF :
                break;

            case LINEMODE_SLC_FORW1 :
                break;

            case LINEMODE_SLC_FORW2 :
                break;

            case LINEMODE_SLC_MCL :
                break;

            case LINEMODE_SLC_MCR :
                break;

            case LINEMODE_SLC_MCWL :
                break;

            case LINEMODE_SLC_MCWR :
                break;

            case LINEMODE_SLC_MCBOL :
                break;

            case LINEMODE_SLC_MCEOL :
                break;

            case LINEMODE_SLC_INSRT :
                break;

            case LINEMODE_SLC_OVER :
                break;

            case LINEMODE_SLC_ECR :
                break;

            case LINEMODE_SLC_EWR :
                break;

            case LINEMODE_SLC_EBOL :
                break;

            case LINEMODE_SLC_EEOL :
                break;
        }

        return null;
    }

    public function write($data, $sendQPacket = FALSE)
    {
        $bytes = 0;
        $dataLen = strlen($data);
        if ($dataLen == 0)
            return 0;

        if (!is_resource($this->socket))
            return $bytes;

        if ($sendQPacket == TRUE) {
            // This packet came from the sendQ. We just try to send this and don't bother too much about error checking.
            // That's done from the sendQ flushing code.
            $bytes = @fwrite($this->socket, $data);
        } else {
            if ($this->sendQLen == 0) {
                // It's Ok to send packet
                $bytes = @fwrite($this->socket, $data);
                $this->lastActivity = time();

                if (!$bytes || $bytes != $dataLen) {
                    // Could not send everything in one go - send the remainder to sendQ
                    $this->addPacketToSendQ (substr($data, $bytes));
                }
            } else {
                // Remote is lagged
                $this->addPacketToSendQ($data);
            }
        }

        return $bytes;
    }

    public function &getSendQLen()
    {
        return $this->sendQLen;
    }

    private function addPacketToSendQ($data)
    {
        $this->sendQ			.= $data;
        $this->sendQLen			+= strlen($data);
    }

    public function flushSendQ()
    {
        // Send chunk of data
        $bytes = $this->write(substr($this->sendQ, 0, $this->sendWindow), TRUE);

        // Dynamic window sizing
        if ($bytes == $this->sendWindow)
            $this->sendWindow += STREAM_WRITE_BYTES;
        else {
            $this->sendWindow -= STREAM_WRITE_BYTES;
            if ($this->sendWindow < STREAM_WRITE_BYTES)
                $this->sendWindow = STREAM_WRITE_BYTES;
        }

        // Update the sendQ
        $this->sendQ = substr($this->sendQ, $bytes);
        $this->sendQLen -= $bytes;

        // Cleanup / reset timers
        if ($this->sendQLen == 0) {
            // All done flushing - reset queue variables
            $this->sendQReset();
        } elseif ($bytes > 0) {
            // Set when the last packet was flushed
            $this->lastActivity		= time();
        }
        //console('Bytes sent : '.$bytes.' - Bytes left : '.$this->sendQLen);
    }

    private function sendQReset()
    {
        $this->sendQ			= '';
        $this->sendQLen			= 0;
        $this->lastActivity		= time();
    }
}
