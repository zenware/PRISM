<?php

namespace PRISM\Module\Telnet\Screen;
use PRISM\Module\Telnet\Screen\Object as ScreenObject;

/**
 * ScreenContainer is a base class that can contain other screen objects.
*/
abstract class Container extends ScreenObject // use as ScreenContainer
{
    protected $screenObjects		= array();

    public function add(ScreenObject $object)
    {
        $this->screenObjects[] = $object;
    }

    public function remove(ScreenObject $object)
    {
        foreach ($this->screenObjects as $index => $ob) {
            if ($object === $ob) {
                unset($this->screenObjects[$index]);
                break;
            }
        }
    }

    public function removeAll()
    {
        $this->screenObjects = array();
    }

    public function removeById($objectId)
    {
        foreach ($this->screenObjects as $index => $ob) {
            if ($objectId == $ob->getId()) {
                unset($this->screenObjects[$index]);
                break;
            }
        }
    }

    public function getObjectById($objectId)
    {
        foreach ($this->screenObjects as $index => $ob) {
            if ($objectId == $ob->getId()) {
                return $ob;
            }
        }
        return null;
    }

    public function getObjectByIndex($index)
    {
        if (isset($this->screenObjects[$index]))
            return $this->screenObjects[$index];
        return null;
    }

    public function getNumObjects()
    {
        return count($this->screenObjects);
    }

    public function draw()
    {
        $screenBuf = '';
        $this->realWidth = $this->getWidth();
        $this->realHeight = $this->getHeight();

        foreach ($this->screenObjects as $object) {
            if (!$object->isVisible())
                continue;

            // Draw the object and place it on its x and y
            $screenBuf .= KEY_ESCAPE.'['.$object->getY().';'.$object->getX().'H';
            $screenBuf .= $object->draw();
        }

        if ($this->getBorder()) {
            $screenBuf .= KEY_ESCAPE.'['.$this->getY().';'.$this->getX().'H';
            $screenBuf .= $this->drawBorder();
        }

        return $screenBuf;
    }

    public function updateTTypes($ttype)
    {
        foreach ($this->screenObjects as $object) {
            $object->setTType($ttype);
            if (is_subclass_of($object, 'ScreenContainer'))
                $object->updateTTypes($ttype);
            $object->clearCache();
        }
    }
}
