<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 23/09/17
 * Time: 11:14
 */

namespace Weathermap\Core;

class HTMLImagemapAreaRectangle extends HTMLImagemapArea
{
    public $x1;
    public $x2;
    public $y1;
    public $y2;

    public function __construct($name = "", $href = "", $coords)
    {

        $c = $coords[0];

        $x1 = round($c[0]);
        $y1 = round($c[1]);
        $x2 = round($c[2]);
        $y2 = round($c[3]);

        // sort the points, so that the first is the top-left
        if ($x1 > $x2) {
            $this->x1 = $x2;
            $this->x2 = $x1;
        } else {
            $this->x1 = $x1;
            $this->x2 = $x2;
        }

        if ($y1 > $y2) {
            $this->y1 = $y2;
            $this->y2 = $y1;
        } else {
            $this->y1 = $y1;
            $this->y2 = $y2;
        }

        $this->name = $name;
        $this->href = $href;
    }

    public function hitTest($x, $y)
    {
        return (($x > $this->x1) && ($x < $this->x2) && ($y > $this->y1) && ($y < $this->y2));
    }

    public function asHTML()
    {
        $coordstring = join(",", array($this->x1, $this->y1, $this->x2, $this->y2));
        return '<area ' . $this->commonHTML() . 'shape="rect" coords="' . $coordstring . '" />';
    }

    public function asJSON()
    {
        $json = "{ \"shape\":'rect', ";
        $json .= " \"x1\":" . $this->x1 . ", \"y1\":" . $this->y1 . ",";
        $json .= " \"x2\":" . $this->x2 . ", \"y2\":" . $this->y2 . ", \"name\":'" . $this->name . "'}";

        return $json;
    }

    public function __toString()
    {
        return sprintf("Rectangle[%d,%d-%d,%d]", $this->x1, $this->y1, $this->x2, $this->y2);
    }
}
