<?php

namespace PRISM\Module\Telnet\Screen;

define('TS_BORDER_NONE',		0);
define('TS_BORDER_REGULAR',		1);
define('TS_BORDER_DOUBLE',		2);
define('TS_BORDER_NUMTYPES',	3);

define('TC_BORDER_TOPLEFT',		0);
define('TC_BORDER_TOPRIGHT',	1);
define('TC_BORDER_BOTTOMLEFT',	2);
define('TC_BORDER_BOTTOMRIGHT',	3);
define('TC_BORDER_HORILINE',	4);
define('TC_BORDER_VERTLINE',	5);

class BorderHelper // Use as ScreenBorderHelper
{
    private $ttype		= 0;

    public function __construct($ttype)
    {
        $this->ttype	= $ttype;
    }

    public function start()
    {
        if ($this->ttype == TELNET_TTYPE_XTERM)
            return VT100_STYLE_RESET.VT100_USG0_LINE;

        return '';
    }

    public function end()
    {
        if ($this->ttype == TELNET_TTYPE_XTERM)
            return VT100_STYLE_RESET.VT100_USG0;

        return '';
    }

    public function getChar($type)
    {
        switch($type) {
            case TC_BORDER_TOPLEFT :
                if ($this->ttype == TELNET_TTYPE_XTERM)
                    return chr(108);
                else if ($this->ttype == TELNET_TTYPE_ANSI)
                    return chr(218);
                else
                    return '/';

            case TC_BORDER_TOPRIGHT :
                if ($this->ttype == TELNET_TTYPE_XTERM)
                    return chr(107);
                else if ($this->ttype == TELNET_TTYPE_ANSI)
                    return chr(191);
                else
                    return '\\';

            case TC_BORDER_BOTTOMLEFT :
                if ($this->ttype == TELNET_TTYPE_XTERM)
                    return chr(109);
                else if ($this->ttype == TELNET_TTYPE_ANSI)
                    return chr(192);
                else
                    return '\\';

            case TC_BORDER_BOTTOMRIGHT :
                if ($this->ttype == TELNET_TTYPE_XTERM)
                    return chr(106);
                else if ($this->ttype == TELNET_TTYPE_ANSI)
                    return chr(217);
                else
                    return '/';

            case TC_BORDER_HORILINE :
                if ($this->ttype == TELNET_TTYPE_XTERM)
                    return chr(113);
                else if ($this->ttype == TELNET_TTYPE_ANSI)
                    return chr(196);
                else
                    return '-';

            case TC_BORDER_VERTLINE :
                if ($this->ttype == TELNET_TTYPE_XTERM)
                    return chr(120);
                else if ($this->ttype == TELNET_TTYPE_ANSI)
                    return chr(179);
                else
                    return '|';
        }

        return '*';
    }
}
