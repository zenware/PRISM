<?php
/**
 * Geometry.php
 * The new and improved superclass for geometry :D
 * Somehow I feel like this can improve other stuff.
 * 
 * @category   Superclass
 * @package    PRISM
 * @subpackage Module\Geometry
 * @author     zenware (Jay Looney) <jay.m.looney@gmail.com>
 * @license    http://opensource.org/license/MIT MIT License
 * @link       https://github.com/zenware/PRISM/blob/devel/Module/Geometry.php 
 */

namespace PRISM\Module;

/**
 * PHPInSimMod - Geometry Module
 * Classes that used to include PRISM geometry will now need to explicitly pull in each Geometry subclass
 *
 * @package    PRISM
 * @subpackage Module\Geometry
 * @author     zenware (Jay Looney) <jay.m.looney@gmail.com>
*/
abstract class Geometry
{
    abstract public function area();
    abstract public function centroid();
    abstract public function boundary();
    abstract public function contains();
    abstract public function x();
    abstract public function y();
    abstract public function numPoints();
    abstract public function dimension();
    abstract public function isEmpty();
    abstract public function asArray();
    abstract public function explode(); // Can we override this?
    abstract public function getPoints();

    // Aliases :D
    public function getArea() {
        return $this->area();
    }

    public function getCentroid() {
        return $this->centroid();
    }

    public function getX() {
        return $this->x();
    }

    public function getY() {
        return $this->y();
    }
}
