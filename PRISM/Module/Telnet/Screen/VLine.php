<?php

namespace PRISM\Module\Telnet\Screen;
use PRISM\Module\Telnet\Screen\Object as ScreenObject;

class VLine extends ScreenObject // use as TSVLine
{
    public function __construct($x, $y, $height)
    {
        $this->setLocation($x, $y);
        $this->setHeight($height);
    }

    public function draw()
    {
        $bHelp = new ScreenBorderHelper($this->getTType());

        $screenBuf = $bHelp->start();
        for ($a=0; $a<$this->getHeight(); $a++) {
            $screenBuf .= $bHelp->getChar(TC_BORDER_VERTLINE);
            $screenBuf .= KEY_ESCAPE.'[B'.KEY_ESCAPE.'[D';
        }
        $screenBuf .= $bHelp->end();

        return $screenBuf;
    }
}
