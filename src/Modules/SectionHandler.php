<?php
/**
 * PHPInSimMod - SectionHandler Module
 * @package PRISM
 * @subpackage SectionHandler
*/

namespace PRISM\Modules;

abstract class SectionHandler extends IniLoader
{
    abstract public function initialise();
}
