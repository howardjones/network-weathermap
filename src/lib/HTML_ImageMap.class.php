<?php
// Copyright Howard Jones, 2005 howie@thingy.com
// http://wotsit.thingy.com/haj/cacti/
// Released under the GNU Public License

// A simple port of the guts of Apache's mod_imap
// - if you have an image control in a form, it's not really defined what happens to USEMAP
//   attributes. They are allowed in HTML 4.0 and XHTML, but some testing shows that they're
//   basically ignored. So you need to use server-side imagemaps if you want to have a form
//   where you are choosing a verb from (for example) a <SELECT> and also specifying part of
//   an image with an IMAGE control.
//
//
class HTMLImageMapArea
{
    public $href;
    public $name;
    public $cssID;
    public $alt;
    public $zOrder;
    public $extraHTML;

    protected function commonHTML()
    {
        $html = "";
        if ($this->name != "") {
            $html .= "id=\"" . $this->name . "\" ";
        }

        if ($this->href != "") {
            $html .= "href=\"" . $this->href . "\" ";
        } else {
            $html .= "nohref ";
        }

        if ($this->extraHTML != "") {
            $html .= $this->extraHTML . " ";
        }
        return $html;
    }
}

class HTMLImageMapAreaPolygon extends HTMLImageMapArea
{
    private $points = array();
    private $minX;
    private $maxX;
    private $minY;
    private $maxY; // bounding box
    private $nPoints;

    public function asHTML()
    {
        $flatPoints = array();
        foreach ($this->points as $point) {
            $flatPoints[] = $point[0];
            $flatPoints[] = $point[1];
        }
        $coordString = join(",", $flatPoints);

        return '<area ' . $this->commonHTML() . 'shape="poly" coords="' . $coordString . '" />';
    }

    public function asJSON()
    {
        $json = "{ \"shape\":'poly', \"npoints\":" . $this->nPoints . ", \"name\":'" . $this->name . "',";

        $xList = '';
        $yList = '';
        foreach ($this->points as $point) {
            $xList .= $point[0] . ",";
            $yList .= $point[1] . ",";
        }
        $xList = rtrim($xList, ", ");
        $yList = rtrim($yList, ", ");
        $json .= " \"x\": [ $xList ], \"y\":[ $yList ], \"minx\": " . $this->minX . ", \"miny\": " . $this->minY . ", \"maxx\":" . $this->maxX . ", \"maxy\":" . $this->maxY . "}";

        return ($json);
    }

    public function hitTest($x, $y)
    {
        $result = 0;
        // do the easy bounding-box test first.
        if (($x < $this->minX) || ($x > $this->maxX) || ($y < $this->minY) || ($y > $this->maxY)) {
            return false;
        }

        // Algorithm from from
        // http://www.ecse.rpi.edu/Homepages/wrf/Research/Short_Notes/pnpoly.html#The%20C%20Code
        for ($i = 0, $j = $this->nPoints - 1; $i < $this->nPoints; $j = $i++) {
            $x1 = $this->points[$i][0];
            $y1 = $this->points[$i][1];
            $x2 = $this->points[$j][0];
            $y2 = $this->points[$j][1];

            if (((($y1 <= $y) && ($y < $y2)) || (($y2 <= $y) && ($y < $y1))) && ($x < ($x2 - $x1) * ($y - $y1) / ($y2 - $y1) + $x1)) {
                $result = !$result;
            }
        }

        return $result;
    }

    public function draw($gdImageRef, $colour)
    {
        $pts = array();
        foreach ($this->points as $point) {
            $pts[] = $point[0];
            $pts[] = $point[1];
        }
        imagepolygon($gdImageRef, $pts, count($pts) / 2, $colour);
    }

    public function __construct($name = "", $href = "", $coords = array())
    {
        $xList = array();
        $yList = array();
        $points = $coords[0];

        $this->name = $name;
        $this->href = $href;
        $this->nPoints = count($points) / 2;

        if (intval($this->nPoints) != ($this->nPoints)) {
            throw new Exception('Odd number of array elements (' . $this->nPoints . ') in HTML_ImageMap_Area_Polygon!');
        }

        for ($i = 0; $i < $this->nPoints; $i += 2) {
            $point_x = round($points[$i]);
            $point_y = round($points[$i + 1]);
            $point = array($point_x, $point_y);
            $xList[] = $point_x; // these two are used to get the bounding box in a moment
            $yList[] = $point_y;
            $this->points[] = $point;
        }

        $this->minX = min($xList);
        $this->maxX = max($xList);
        $this->minY = min($yList);
        $this->maxY = max($yList);
    }
}

class HTMLImageMapAreaRectangle extends HTMLImageMapArea
{
    private $topLeftX;
    private $bottomRightX;
    private $topLeftY;
    private $bottomRightY;

    public function __construct($name = "", $href = "", $coords = array())
    {
        $points = $coords[0];

        if (count($points) != 4) {
            throw new Exception('Incorrect n1umber of array elements in HTML_ImageMap_Area_Rectangle!');
        }

        $point1_x = round($points[0]);
        $point1_y = round($points[1]);
        $point2_x = round($points[2]);
        $point2_y = round($points[3]);

        // sort the points, so that the first is the top-left
        if ($point1_x > $point2_x) {
            $this->topLeftX = $point2_x;
            $this->bottomRightX = $point1_x;
        } else {
            $this->topLeftX = $point1_x;
            $this->bottomRightX = $point2_x;
        }

        if ($point1_y > $point2_y) {
            $this->topLeftY = $point2_y;
            $this->bottomRightY = $point1_y;
        } else {
            $this->topLeftY = $point1_y;
            $this->bottomRightY = $point2_y;
        }

        $this->name = $name;
        $this->href = $href;
    }

    public function hitTest($x, $y)
    {
        return (($x > $this->topLeftX) && ($x < $this->bottomRightX)
            && ($y > $this->topLeftY) && ($y < $this->bottomRightY));
    }

    public function asHTML()
    {
        $coordstring = join(",", array($this->topLeftX, $this->topLeftY, $this->bottomRightX, $this->bottomRightY));
        return '<area ' . $this->commonHTML() . 'shape="rect" coords="' . $coordstring . '" />';
    }

    public function asJSON()
    {
        $json = "{ \"shape\":'rect', ";
        $json .= " \"x1\":" . $this->topLeftX . ", \"y1\":" . $this->topLeftY . ", ";
        $json .= "\"x2\":" . $this->bottomRightX . ", \"y2\":" . $this->bottomRightY . ", ";
        $json .= "\"name\":'" . $this->name . "'}";

        return ($json);
    }


    public function draw($gdImageRef, $colour)
    {
        imagerectangle(
            $gdImageRef,
            $this->topLeftX,
            $this->topLeftY,
            $this->bottomRightX,
            $this->bottomRightY,
            $colour
        );
    }
}

class HTMLImageMapAreaCircle extends HTMLImageMapArea
{
    private $centreX;
    private $centreY;
    private $edgeX;
    private $edgeY;

    public function asHTML()
    {
        $coordString = join(",", array($this->centreX, $this->centreY, $this->edgeX, $this->edgeY));
        return '<area ' . $this->commonHTML() . 'shape="circle" coords="' . $coordString . '" />';
    }

    public function hitTest($x, $y)
    {
        $radius1 = ($this->edgeY - $this->centreY) * ($this->edgeY - $this->centreY)
            + ($this->edgeX - $this->centreX) * ($this->edgeX - $this->centreX);

        $radius2 = ($this->centreY - $y) * ($this->centreY - $y)
            + ($this->centreX - $x) * ($this->centreX - $x);

        return ($radius2 <= $radius1);
    }

    public function draw($gdImageRef, $colour)
    {
        $radius = abs($this->centreX - $this->edgeX);
        imageellipse($gdImageRef, $this->centreX, $this->centreY, $radius, $radius, $colour);
    }

    public function __construct($name = "", $href = "", $coords = array())
    {
        $points = $coords[0];

        $this->name = $name;
        $this->href = $href;
        $this->centreX = round($points[0]);
        $this->centreY = round($points[1]);
        $this->edgeX = round($points[2]);
        $this->edgeY = round($points[3]);
    }
}

class HTMLImageMap
{
    private $shapes;
    private $nShapes;
    private $name;
    private $zLayers;

    public function __construct($name = "")
    {
        $this->Reset();
        $this->name = $name;
    }

    public function Reset()
    {
        $this->shapes = array();
        $this->zLayers = array();
        $this->nShapes = 0;
        $this->name = "";
    }


    /**
     * Draw the outlines for the imagemap - for debugging
     *
     * @param GDImageRef $gdImageRef
     * @param GDColorRef $colour
     */
    public function draw($gdImageRef, $colour)
    {
        foreach ($this->shapes as $shape) {
            $shape->draw($gdImageRef, $colour);
        }
    }

    // add an element to the map - takes an array with the info, in a similar way to HTML_QuickForm
    /**
     *
     * @param string OR object $element
     * @param optional string name
     * @param optional string href
     * @param optional type-specific stuff
     * @return null
     */
    public function addArea($element)
    {
        if (is_object($element) && is_subclass_of($element, 'HTMLImageMapArea')) {
            $elementObject = &$element;
        } else {
            $args = func_get_args();
            $className = "HTMLImageMapArea" . $element;
            $elementObject = new $className($args[1], $args[2], array_slice($args, 3));
        }

        $this->shapes[$elementObject->name] = &$elementObject;
        $this->nShapes++;
    }

    // do a hit-test based on the current map
    // - can be limited to only match elements whose names match the filter
    //   (e.g. pick a building, in a campus map)
    public function hitTest($x, $y, $nameFilter = "")
    {
        $preg = '/' . $nameFilter . '/';
        foreach ($this->shapes as $shape) {
            if ($shape->hitTest($x, $y)) {
                if (($nameFilter == "") || (preg_match($preg, $shape->name))) {
                    return $shape->name;
                }
            }
        }
        return false;
    }

    // update a property on all elements in the map that match a name
    // (use it for retro-actively adding in link information to a pre-built geometry before generating HTML)
    // returns the number of elements that were matched/changed
    public function setProp($which, $what, $where)
    {
        $count = 0;
        if (true === isset($this->shapes[$where])) {
            switch ($which) {
                case 'href':
                    $this->shapes[$where]->href = $what;
                    $count++;
                    break;
                case 'extrahtml':
                    $this->shapes[$where]->extrahtml = $what;
                    $count++;
                    break;
            }
        }

        return $count;
    }

    // update a property on all elements in the map that match a name as a substring
    // (use it for retro-actively adding in link information to a pre-built geometry before generating HTML)
    // returns the number of elements that were matched/changed
    public function setPropSub($which, $what, $where)
    {
        $count = 0;
        for ($i = 0; $i < count($this->shapes); $i++) {
            if (($where == "") || (strstr($this->shapes[$i]->name, $where) != false)) {
                switch ($which) {
                    case 'href':
                        $this->shapes[$i]->href = $what;
                        break;
                    case 'extrahtml':
                        $this->shapes[$i]->extrahtml = $what;
                        break;
                }
                $count++;
            }
        }
        return $count;
    }

    // Return the imagemap as an HTML client-side imagemap for inclusion in a page
    public function asHTML()
    {
        $html = '<map';
        if ($this->name != "") {
            $html .= ' name="' . $this->name . '"';
        }
        $html .= ">\n";
        foreach ($this->shapes as $shape) {
            $html .= $shape->asHTML() . "\n";
            $html .= "\n";
        }
        $html .= "</map>\n";

        return $html;
    }

    public function subJSON($nameFilter = "", $reverseOrder = false)
    {
        $json = '';

        $preg = '/' . $nameFilter . '/';
        foreach ($this->shapes as $shape) {
            if (($nameFilter == "") || (preg_match($preg, $shape->name))) {
                if ($reverseOrder) {
                    $json = $shape->asJSON() . ",\n" . $json;
                } else {
                    $json .= $shape->asJSON() . ",\n";
                }
            }
        }
        $json = rtrim($json, "\n, ");
        $json .= "\n";

        return $json;
    }

    // return HTML for a subset of the map, specified by the filter string
    // (suppose you want some partof your UI to have precedence over another part
    //  - the imagemap is checked from top-to-bottom in the HTML)
    // - skipnolinks -> in normal HTML output, we don't need areas for things with no href
    public function subHTML($nameFilter = "", $reverseOrder = false, $skipNoLinks = false)
    {
        $html = "";

        foreach ($this->shapes as $shape) {
            if (($nameFilter == "") || (strstr($shape->name, $nameFilter) !== false)) {
                if (!$skipNoLinks || $shape->href != "" || $shape->extrahtml != "") {
                    if ($reverseOrder) {
                        $html = $shape->asHTML() . "\n" . $html;
                    } else {
                        $html .= $shape->asHTML() . "\n";
                    }
                }

            }
        }
        return $html;
    }

    public function exactHTML($name = '', $reverseOrder = false, $skipNoLinks = false)
    {
        $html = '';

        $shape = $this->shapes[$name];

        if (true === isset($shape)) {
            if ((false === $skipNoLinks) || ($shape->href !== '') || ($shape->extraHTML !== '')) {
                if ($reverseOrder === true) {
                    $html = $shape->asHTML() . "\n" . $html;
                } else {
                    $html .= $shape->asHTML() . "\n";
                }
            }
        }

        return $html;
    }
}
// vim:ts=4:sw=4:
