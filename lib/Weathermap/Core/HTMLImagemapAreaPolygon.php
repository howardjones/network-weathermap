<?php

namespace Weathermap\Core;

/**
 * A polygon for HTMLImagemap (used for link arrows)
 *
 * @package Weathermap\Core
 */
class HTMLImagemapAreaPolygon extends HTMLImagemapArea
{
    public $points = array();
    public $minx;
    public $maxx;
    public $miny;
    public $maxy; // bounding box
    public $npoints;

    public function asHTML()
    {
        $flatpoints = array();
        foreach ($this->points as $point) {
            $flatpoints[] = $point[0];
            $flatpoints[] = $point[1];
        }
        $coordstring = join(',', $flatpoints);

        return '<area ' . $this->commonHTML() . 'shape="poly" coords="' . $coordstring . '" />';
    }

    public function asJSONData()
    {
        $data = array(
            'shape' => 'poly',
            'npoints' => $this->npoints,
            'name' => $this->name,
            'href' => $this->href,
            'extrahtml' => $this->extrahtml,
            'points' => array(),
            'minx' => $this->minx,
            'miny' => $this->miny,
            'maxx' => $this->maxx,
            'maxy' => $this->maxy
        );
        foreach ($this->points as $point) {
            $data['points'] [] = array($point[0], $point[1]);
        }

        return $data;
    }

    public function hitTest($x, $y)
    {
        $c = false;
        // do the easy bounding-box test first.
        if (($x < $this->minx) || ($x > $this->maxx) || ($y < $this->miny) || ($y > $this->maxy)) {
            return false;
        }

        // Algorithm from
        // http://www.ecse.rpi.edu/Homepages/wrf/Research/Short_Notes/pnpoly.html#The%20C%20Code
        for ($i = 0, $j = $this->npoints - 1; $i < $this->npoints; $j = $i++) {
            $x1 = $this->points[$i][0];
            $y1 = $this->points[$i][1];
            $x2 = $this->points[$j][0];
            $y2 = $this->points[$j][1];

            if (((($y1 <= $y) && ($y < $y2)) || (($y2 <= $y) && ($y < $y1))) &&
                ($x < ($x2 - $x1) * ($y - $y1) / ($y2 - $y1) + $x1)
            ) {
                $c = !$c;
            }
        }

        return $c;
    }

    public function __construct($coords, $name = '', $href = '')
    {
        parent::__construct();

        $c = $coords[0];

        $this->name = $name;
        $this->href = $href;
        $this->npoints = count($c) / 2;

        if (intval($this->npoints) != ($this->npoints)) {
            throw new \Exception('Odd number of points!');
        }

        $xlist = array();
        $ylist = array();

        for ($i = 0; $i < count($c); $i += 2) {
            $x = round($c[$i]);
            $y = round($c[$i + 1]);
            $point = array($x, $y);
            $xlist[] = $x; // these two are used to get the bounding box in a moment
            $ylist[] = $y;
            $this->points[] = $point;
        }

        $this->minx = min($xlist);
        $this->maxx = max($xlist);
        $this->miny = min($ylist);
        $this->maxy = max($ylist);
    }

    public function __toString()
    {
        return 'Polygon';
    }
}
