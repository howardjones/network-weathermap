<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 23/09/17
 * Time: 11:15
 */

namespace Weathermap\Core;

class HTMLImagemapAreaCircle extends HTMLImagemapArea
{
    public $centx;
    public $centy;
    public $edgex;
    public $edgey;

    public function asHTML()
    {
        $coordstring = join(',', array($this->centx, $this->centy, $this->edgex, $this->edgey));
        return '<area ' . $this->commonHTML() . 'shape="circle" coords="' . $coordstring . '" />';
    }

    public function hitTest($x, $y)
    {
        $radius1 = ($this->edgey - $this->centy) * ($this->edgey - $this->centy)
            + ($this->edgex - $this->centx) * ($this->edgex - $this->centx);

        $radius2 = ($this->centy - $y) * ($this->centy - $y)
            + ($this->centx - $x) * ($this->centx - $x);

        return $radius2 <= $radius1;
    }

    public function __construct($coords, $name = '', $href = '')
    {
        $c = $coords[0];

        $this->name = $name;
        $this->href = $href;
        $this->centx = round($c[0]);
        $this->centy = round($c[1]);
        $this->edgex = round($c[2]);
        $this->edgey = round($c[3]);
    }

    public function __toString()
    {
        return 'Circle';
    }
}
