<?php

namespace PRISM\Module\Telnet\Screen;
use PRISM\Module\Telnet\Screen\Container as ScreenContainer;

abstract class Section extends ScreenContainer // Use as TSSection
{
    abstract public function handleKey($key);
    abstract protected function selectItem();
    abstract protected function setInputMode();

    // Section info
    private $active	      = false;		// Whether this section has KB focus
    private $curItem      = -1;			// pointer to selected item
    protected $subSection = null;			// This holds the currently selected subsection object (another TSSection)

    protected $parentSection = null;			// Parent section object, so we can recursively go down AND up

    public function __construct(ScreenContainer $parentSection)
    {
        $this->parentSection = $parentSection;
    }

    public function __destruct()
    {
        $this->subSection = null;
        $this->parentSection = null;
    }

    protected function resetSection($hard = false)
    {
        $this->curItem = -1;

        if ($hard) {
            // Why does this exist?
        }
    }

    protected function setInputCallback($class, $func = null, $editMode = 0, array $curPos = array(0, 0), $defaultText = '', $maxLength = 23)
    {
        if (get_class($this->parentSection) == 'PrismTelnet') {
            if ($class === null) {
                $this->parentSection->registerInputCallback($this->parentSection, 'handleKey');
                $this->parentSection->setPostCurPos(array());
            } else {
                $this->parentSection->registerInputCallback($class, $func, $editMode);
                $this->parentSection->setLineBuffer($defaultText);
                $this->parentSection->setInputBufferMaxLen($maxLength);
                if ($editMode)
                    $this->parentSection->setPostCurPos($curPos);
                else
                    $this->parentSection->setPostCurPos(array());
            }
//			console('Recursive : found final parent');
        } else {
            $this->parentSection->setInputCallback($class, $func, $editMode, $curPos, $defaultText, $maxLength);
//			console('Recursive : continuing up');
        }
    }

    protected function getLine()
    {
        if (get_class($this->parentSection) == 'PrismTelnet') {
            return $this->parentSection->getLine(true);
        } else {
            return $this->parentSection->getLine();
        }
    }

    public function setActive($active)
    {
        $this->active = (boolean) $active;

        if ($this->getCurObject() === null) {
            return;
        }

        if ($active) {
            $this->getCurObject()->setSelected(true);
            // console('ACTIVATING '.$this->getCurObject()->getId());
            $this->setInputMode();
        } else {
            $this->getCurObject()->setSelected(false);
            $this->setInputCallback(null);
            // console('DE-ACTIVATING'.$this->getCurObject()->getId());
        }
    }

    public function getActive()
    {
        return $this->active;
    }

    protected function getCurObject()
    {
        if ($this->curItem == -1) {
            return $this->nextItem(true);
        }

        return $this->getObjectByIndex($this->curItem);
    }

    protected function nextItem($first = false)
    {
        // find selected object
        $old = null;
        $a = ($this->curItem < 0) ? 0 : $this->curItem;
        while ($object = $this->getObjectByIndex($a)) {
            if ($old === null) {
                if ($first && $object->getOptions() & TS_OPT_ISSELECTABLE) {
                    return $object;
                }

                if ($object->getOptions() & TS_OPT_ISSELECTED) {
                    $old = $object;
                }
            } else {
                if ($object->getOptions() & TS_OPT_ISSELECTABLE) {
                    // Input TextArea lost focus 'the good way' - we need to grab linebufer and store it in old object
                    if ($old->getOptions() & TS_OPT_ISEDITABLE) {
                        $old->setText($this->getLine());
                    }

                    $old->toggleSelected();
                    $object->toggleSelected();
                    $this->curItem = $a;

                    return $object;
                }
            }

            $a++;
        }

        return null;
    }

    protected function previousItem()
    {
        $old = null;
        $a = ($this->curItem < 0) ? ($this->getNumObjects() -1) : $this->curItem;

        while ($object = $this->getObjectByIndex($a)) {
            if ($old === null) {
                if ($object->getOptions() & TS_OPT_ISSELECTED) {
                    $old = $object;
                }
            } else {
                if ($object->getOptions() & TS_OPT_ISSELECTABLE) {
                    // Input TextArea lost focus 'the good way' - we need to grab linebufer and store it in old object
                    if ($old->getOptions() & TS_OPT_ISEDITABLE) {
                        $old->setText($this->getLine());
                    }

                    $old->toggleSelected();
                    $object->toggleSelected();
                    $this->curItem = $a;
                    return $object;
                }
            }

            $a--;
        }

        return null;
    }
}
