<?php

namespace PRISM\Module\Telnet\Screen;
use PRISM\Module\Telnet\Screen\Object as ScreenObject;

class TextArea extends ScreenObject // Use as TSTextArea
{
    protected $text		= '';

    public function __construct($x = 0, $y = 0, $cols = 0, $lines = 0)
    {
        $this->setLocation($x, $y);
        $this->setSize($cols, $lines);
    }

    public function getText()
    {
        return $this->text;
    }

    public function setText($text)
    {
        $this->text			= $text;
        $this->screenCache	= '';
    }

    public function draw()
    {
        if ($this->screenCache != ''){
            return $this->screenCache;
        }

        $screenBuf = '';
        $screenMargin = 0;
        $pos = 0;
        $this->realWidth = 0;
        $this->realHeight = 0;

        if ($this->getBorder() || $this->getCaption()) {
            // Increase screenMargin by one, to indicate this object will be surrounded by one 'pixel'
            $screenMargin++;

            // move the cursor down a line, for content. We'll draw the border after that.
            $screenBuf .= KEY_ESCAPE.'[1B';

            // Count this top line
            $this->realHeight++;
        }

        $style = '';
        if (($this->getOptions() & TS_OPT_ISEDITABLE) == 0 && ($this->getOptions() & TS_OPT_HASBACKGROUND || $this->getOptions() & TS_OPT_ISSELECTED))
            $style .= VT100_STYLE_REVERSE;
        if ($this->getOptions() & TS_OPT_BOLD)
            $style .= VT100_STYLE_BOLD;

        $screenBuf .= $style;

        // Draw content (text)
        foreach ($this->prepareTags() as $word) {
            $wLen = strlen($word[0]);

            // If regular word, check for line wrapping and such
            if ($word[1] == 0) {
                // Skip space at start of line (after line wrap)?
                if ($pos <= $screenMargin && $word[0] == '')
                    continue;

                // Line wrap?
                if ($pos + $wLen > $this->getWidth() - $screenMargin || $word[0] == KEY_ENTER) {
                    // Padding until the end of cols
                    while ($pos < $this->getWidth() - $screenMargin) {
                        $screenBuf .= ' ';
                        $pos++;
                        if ($pos > $this->realWidth)
                            $this->realWidth = $pos;
                    }

                    // Stop if we've ran out of space (include screenMargin to check for bottom border
                    if (++$this->realHeight == $this->getHeight()) {
                        break;
                    }

                    // Line wrap
                    $screenBuf .= KEY_ESCAPE.'['.$pos.'D'.KEY_ESCAPE.'[1B';
                    $pos = 0;

                    if ($word[0] == KEY_ENTER || $word[0] == ' ') {
                        continue;
                    }
                }

                // compensate for (left)margin?
                if ($pos == 0 && $screenMargin > 0) {
                    $screenBuf .= KEY_ESCAPE.'['.$screenMargin.'C';
                    $pos += $screenMargin;

                    if ($pos > $this->realWidth) {
                        $this->realWidth = $pos;
                    }
                }

                $pos += $wLen;
                $screenBuf .= $word[0];

                if ($pos > $this->realWidth) {
                    $this->realWidth = $pos;
                }
            } else {
                // Add style tag (not a word)
                $screenBuf .= $word[0];

                // Reactivate background after a style reset?
                if ($word[0] == VT100_STYLE_RESET) {
                    $screenBuf .= $style;
                }
            }
        }

        // Padding until the end of cols
        while ($pos < $this->getWidth() - $screenMargin) {
            $screenBuf .= ' ';
            $pos++;
            if ($pos > $this->realWidth) {
                $this->realWidth = $pos;
            }
        }

        // Turn off background?
        if ($style != '') {
            $screenBuf .= VT100_STYLE_RESET;
        }

        // Still have to count the last line we drew
        $this->realHeight++;

        // If there's a border, increase realWidth by one (to include right border)
        if ($this->getBorder()) {
            $this->realWidth++;
        }

        // If we have to draw a border or caption, do so here
        if ($this->getBorder() || $this->getCaption()) {
            // Compesate realHeight
            if ($this->getBorder()) {
                $this->realHeight += 1;
            }

            $screenBuf .= KEY_ESCAPE.'['.($this->realWidth - 1).'D'.KEY_ESCAPE.'['.($this->realHeight - 2).'A';
            $screenBuf .= $this->drawBorder();

        }

        //console('object width : '.$this->realWidth.' | object height : '.$this->realHeight);

        $this->screenCache = $screenBuf;
        return $screenBuf;
    }

    // Split style tags into their own entry in $words and mark them as such
    private function prepareTags()
    {
        $words = explode(' ', $this->text);
        $out = array();

        foreach ($words as $word) {
            $matches = array();
            if (preg_match_all('/'.KEY_ESCAPE.'\[(\d*)m/', $word, $matches, PREG_OFFSET_CAPTURE)) {
                $cutOffset = 0;
                foreach ($matches[0] as $match) {
                    $mLen = strlen($match[0]);
                    $match[1] -= $cutOffset;

                    // Do we have chars BEFORE this tag? (that means regular chars)
                    if ($match[1] > 0) {
                        // Split those regular chars into its own array entry
                        $exp = explode(KEY_ENTER, substr($word, 0, $match[1]));
                        foreach ($exp as $i => $e) {
                            if ($i > 0) {
                                $out[] = array(KEY_ENTER, 0);
                            }

                            $out[] = array($e, 0);
                        }

                        // Then the tag
                        $out[] = array($match[0], 1);

                        $word = substr($word, $match[1] + $mLen);
                        $cutOffset += $match[1] + $mLen;
                    } else {
                        $out[] = array($match[0], 1);
                        $word = substr($word, $mLen);
                        $cutOffset += $mLen;
                    }
                }
                // Are there still regular chars after all the tags?
                if ($word != '') {
                    $exp = explode(KEY_ENTER, $word);
                    foreach ($exp as $i => $e) {
                        if ($i > 0) {
                            $out[] = array(KEY_ENTER, 0);
                        }

                        $out[] = array($e, 0);
                    }
                }
            } else {
                // Regular word
                $exp = explode(KEY_ENTER, $word);
                foreach ($exp as $i => $e) {
                    if ($i > 0) {
                        $out[] = array(KEY_ENTER, 0);
                    }

                    $out[] = array($e, 0);
                }
            }

            // Add the space between words to $out (it was stripped in the explode())
            $out[] = array(' ', 0);
        }
        array_pop($out);

        return $out;
    }
}
