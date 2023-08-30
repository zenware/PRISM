<?php
/**
 * PHPInSimMod - SectionHandler Module
 * @package PRISM
 * @subpackage SectionHandler
*/
namespace PRISM;

use PRISM\Modules\IniLoader;

abstract class SectionHandler extends IniLoader
{
    abstract public function initialise();
}
