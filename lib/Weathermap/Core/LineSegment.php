<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 23/09/17
 * Time: 11:16
 */

namespace Weathermap\Core;

class LineSegment
{
    public $point1;
    public $point2;
    public $vector;

    public function __construct($p1, $p2)
    {
        $this->point1 = $p1;
        $this->point2 = $p2;

        $this->vector = $p1->vectorToPoint($p2);
    }

    public function __toString()
    {
        return sprintf("{%s--%s}", $this->point1, $this->point2);
    }
}
