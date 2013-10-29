<?php

namespace PRISM\Module\Telnet\Screen;
use PRISM\Module\Telnet\Screen\Object as ScreenObject;

class HLine extends ScreenObject // use as TSHLine
{
    public function __construct($x, $y, $width)
    {
        $this->setLocation($x, $y);
        $this->setWidth($width);
    }

    public function draw()
    {
        $bHelp = new ScreenBorderHelper($this->getTType());

        $screenBuf = $bHelp->start();

        for ($a=0; $a<$this->getWidth(); $a++) {
            $screenBuf .= $bHelp->getChar(TC_BORDER_HORILINE);
        }

        $screenBuf .= $bHelp->end();

        return $screenBuf;
    }
}
