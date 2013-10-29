<?php

namespace PRISM\Module\Telnet;

class MenuBar extends ScreenContainer
{
    public function __construct($ttype)
    {
        $this->setSize(80, 1);
        $this->setId('mainMenu');
        $this->setTType($ttype);

        $textArea = new TSTextArea(3, 1, 6, 1);
        $textArea->setId('admins');
        $textArea->setOptions(TS_OPT_ISSELECTABLE | TS_OPT_ISSELECTED);
        $textArea->setText(VT100_STYLE_BOLD.'A'.VT100_STYLE_RESET.'dmins');
        $this->add($textArea);

        $textArea = new TSTextArea(16, 0, 5, 1);
        $textArea->setId('hosts');
        $textArea->setOptions(TS_OPT_ISSELECTABLE);
        $textArea->setText(VT100_STYLE_BOLD.'H'.VT100_STYLE_RESET.'osts');
        $this->add($textArea);

        $textArea = new TSTextArea(26, 0, 7, 1);
        $textArea->setId('plugins');
        $textArea->setOptions(TS_OPT_ISSELECTABLE);
        $textArea->setText(VT100_STYLE_BOLD.'P'.VT100_STYLE_RESET.'lugins');
        $this->add($textArea);

        $l = strlen('Prism v'.PHPInSimMod::VERSION);
        $textArea = new TSTextArea(80 - ($l + 1), 0, $l, 1);
        $textArea->setId('prismVersion');
        $textArea->setText(VT100_STYLE_GREEN.VT100_STYLE_BOLD.'Prism v'.PHPInSimMod::VERSION.VT100_STYLE_RESET);
        $this->add($textArea);

        $line = new TSHLine(2, 2, $this->getWidth() - 2);
        $line->setTType($this->getTType());
        $this->add($line);
    }

    public function selectSection($section)
    {
        $a = 0;

        while ($object = $this->getObjectByIndex($a)) {
            if ($object->getId() == $section) {
                if (($object->getOptions() & TS_OPT_ISSELECTED) == 0)
                    $object->toggleSelected();
            } else {
                if (($object->getOptions() & TS_OPT_ISSELECTED) > 0) {
                    $object->toggleSelected();
                }
            }

            $a++;
        }
    }
}
