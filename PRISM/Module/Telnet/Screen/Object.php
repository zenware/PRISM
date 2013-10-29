<?php

namespace PRISM\Module\Telnet\Screen;


/**
 * ScreenObject is the base class for all screen components
 * (ScreenContainer, TextLine, TextArea, etc)
*/
abstract class Object
{
    abstract public function draw();

    private $id				= '';
    private $x				= 0;
    private $y				= 0;
    private $absolute		= false;			// Absolute position or relative to parent
    private $cols			= 0;				// width
    protected $realWidth	= 0;
    private $lines			= 0;				// height
    protected $realHeight	= 0;

    private $ttype			= 0;
    private $visible		= true;
    private $border			= TS_BORDER_NONE;	// border type
    private $margin			= 0;				// border margin
    private $caption		= '';
    private $options		= 0;				// Selectable, selected, has background, editable, etc

    protected $screenCache	= '';				// object contents cache

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setTType($ttype)
    {
        $this->ttype = $ttype;
    }

    public function getTType()
    {
        return $this->ttype;
    }

    public function setLocation($x, $y)
    {
        $this->setX($x);
        $this->setY($y);
    }

    public function setVisible($visible)
    {
        $this->visible = $visible;
    }

    public function isVisible()
    {
        return $this->visible;
    }

    public function setX($x)
    {
        $x = (int) $x;
        if ($x < 0)
            $x = 0;
        $this->x		= $x;
    }

    public function setY($y)
    {
        $y = (int) $y;
        if ($y < 0)
            $y = 0;
        $this->y		= $y;
    }

    public function getLocation()
    {
        return array($this->x, $this->y);
    }

    public function getX()
    {
        return $this->x;
    }

    public function getY()
    {
        return $this->y;
    }

    public function setAbsolute($absolute)
    {
        $this->absolute = $absolute;
    }

    public function getAbsolute()
    {
        return $this->absolute;
    }

    public function setSize($cols, $lines)
    {
        $this->setWidth($cols);
        $this->setHeight($lines);
    }

    public function setWidth($cols)
    {
        $cols = (int) $cols;
        if ($cols < 0)
            $cols = 0;
        $this->cols			= $cols;
        $this->screenCache	= '';
    }

    public function setHeight($lines)
    {
        $lines = (int) $lines;
        if ($lines < 0)
            $lines = 0;
        $this->lines		= $lines;
        $this->screenCache	= '';
    }

    public function getSize()
    {
        return array($this->cols, $this->lines);
    }

    public function getWidth()
    {
        return $this->cols;
    }

    public function getHeight()
    {
        return $this->lines;
    }

    public function getRealWidth()
    {
        return $this->realWidth;
    }

    public function getRealHeight()
    {
        return $this->realHeight;
    }

    public function setBorder($border)
    {
        $border = (int) $border;
        if ($border < 0 || $border > TS_BORDER_NUMTYPES)
            $border = 0;
        $this->border = $border;
        $this->screenCache	= '';
    }

    public function getBorder()
    {
        return $this->border;
    }

    public function setMargin($margin)
    {
        $margin = (int) $margin;
        if ($margin < 0)
            $margin = 0;
        $this->margin = $margin;
        $this->screenCache	= '';
    }

    public function getMargin()
    {
        return $this->margin;
    }

    public function getCaption()
    {
        return $this->caption;
    }

    public function setCaption($caption)
    {
        $this->caption		= $caption;
        $this->screenCache	= '';
    }

    public function setOptions($options)
    {
        $this->options = $options;
        $this->screenCache = '';
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function toggleSelected()
    {
        if ($this->options & TS_OPT_ISSELECTABLE) {
            if ($this->options & TS_OPT_ISSELECTED)
                $this->options &= ~TS_OPT_ISSELECTED;
            else
                $this->options |= TS_OPT_ISSELECTED;
            $this->screenCache = '';
        }
    }

    public function setSelected($selected)
    {
        if ($selected)
            $this->options |= TS_OPT_ISSELECTED;
        else
            $this->options &= ~TS_OPT_ISSELECTED;
        $this->screenCache = '';
    }

    public function setBold($bold)
    {
        if ($bold)
            $this->options |= TS_OPT_BOLD;
        else
            $this->options &= ~TS_OPT_BOLD;
    }

    public function clearCache()
    {
        $this->screenCache = '';
    }

    protected function drawBorder()
    {
        $screenBuf = '';

        // Draw own style (backgroud? border?)
        if ($this->getBorder() > TS_BORDER_NONE) {
            // Initialise border helper object
            $bHelp = new ScreenBorderHelper($this->getTType());
            $screenBuf .= $bHelp->start();

            // Draw border stuff
            $line = 0;
            while ($line < $this->getRealHeight()) {
                // Move to new line (if not on line 0)
                if ($line > 0) {
                    $screenBuf .= KEY_ESCAPE.'['.$this->realWidth.'D'.KEY_ESCAPE.'[1B';
                }

                // First and last line
                if ($line == 0 || $line == $this->getRealHeight() - 1) {
                    $pos = 0;
                    while ($pos < $this->realWidth) {
                        if ($line == 0 && $pos == 0)
                            $screenBuf .= $bHelp->getChar(TC_BORDER_TOPLEFT);
                        else if ($line == 0 && $pos == $this->realWidth-1)
                            $screenBuf .= $bHelp->getChar(TC_BORDER_TOPRIGHT);
                        else if ($line == $this->getRealHeight() - 1 && $pos == 0)
                            $screenBuf .= $bHelp->getChar(TC_BORDER_BOTTOMLEFT);
                        else if ($line == $this->getRealHeight() - 1 && $pos == $this->realWidth-1)
                            $screenBuf .= $bHelp->getChar(TC_BORDER_BOTTOMRIGHT);
                        else
                            $screenBuf .= $bHelp->getChar(TC_BORDER_HORILINE);
                        $pos++;
                    }

                    // Caption on border?
                    if ($line == 0 && $this->getCaption() != '') {
                        $screenBuf .= $bHelp->end();

                        $cLen = strlen($this->getCaption());
                        $captionX = floor(($this->realWidth - $cLen) / 2);

                        $screenBuf .= KEY_ESCAPE.'['.($this->realWidth - $captionX).'D';
                        $screenBuf .= $this->getCaption();
                        $screenBuf .= KEY_ESCAPE.'['.($this->realWidth - ($cLen + $captionX)).'C';

                        $screenBuf .= $bHelp->start();
                    }
                } else {
                    // Place border only on first and last char
                    $screenBuf .= $bHelp->getChar(TC_BORDER_VERTLINE);
                    $screenBuf .= KEY_ESCAPE.'['.($this->realWidth - 2).'C';
                    $screenBuf .= $bHelp->getChar(TC_BORDER_VERTLINE);
                }

                $line++;
            }

            // Always end border helper (because we may have to reset charset).
            $screenBuf .= $bHelp->end();
            unset($bHelp);
        } else
        // Caption without border?
        if ($this->getCaption() != '') {
            $cLen = strlen($this->getCaption());
            $captionX = floor(($this->realWidth - $cLen) / 2);
            $screenBuf .= str_pad('', $captionX, ' ');
            $screenBuf .= str_pad($this->getCaption(), $this->realWidth - $captionX, ' ');
        }

        return $screenBuf;
    }
}
