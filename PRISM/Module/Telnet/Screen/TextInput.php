<?php

namespace PRISM\Module\Telnet\Screen;
use PRISM\Module\Telnet\Screen\TextArea as TSTextArea;

class TextInput extends TSTextArea // use as TSTextInput
{
    private $maxLength	= 24;

    public function setMaxLength($maxLength)
    {
        $this->maxLength = (int) $maxLength;
    }

    public function getMaxLength()
    {
        return $this->maxLength;
    }
}
