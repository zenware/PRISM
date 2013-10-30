<?php
/**
 * PHPInSimMod - SectionHandler Module
 * @package PRISM
 * @subpackage SectionHandler
*/

namespace PRISM\Module;
use PRISM\Module\IniLoader;

abstract class SectionHandler extends IniLoader
{
    abstract public function init();
}
