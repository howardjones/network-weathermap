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

class HTMLImagemapArea
{
    public $href;
    public $name;
    public $id;
    public $alt;
    public $z;
    public $extrahtml;

    protected function commonHTML()
    {
        $h = "";
        if ($this->name != "") {
            // $h .= " alt=\"".$this->name."\" ";
            $h .= "id=\"" . $this->name . "\" ";
        }
        if ($this->href != "") {
            $h .= "href=\"" . $this->href . "\" ";
        } else {
            $h .= "nohref ";
        }
        if ($this->extrahtml != "") {
            $h .= $this->extrahtml . " ";
        }
        return $h;
    }
}

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
        foreach ($this->points as $point) {
            $flatpoints[] = $point[0];
            $flatpoints[] = $point[1];
        }
        $coordstring = join(",", $flatpoints);

        return '<area ' . $this->commonHTML() . 'shape="poly" coords="' . $coordstring . '" />';
    }

    public function asJSON()
    {
        $json = "{ \"shape\":'poly', \"npoints\":" . $this->npoints . ", \"name\":'" . $this->name . "',";

        $xlist = '';
        $ylist = '';
        foreach ($this->points as $point) {
            $xlist .= $point[0] . ",";
            $ylist .= $point[1] . ",";
        }
        $xlist = rtrim($xlist, ", ");
        $ylist = rtrim($ylist, ", ");
        $json .= " \"x\": [ $xlist ], \"y\":[ $ylist ],";
        $json .= " \"minx\": " . $this->minx . ", \"miny\": " . $this->miny . ",";
        $json .= " \"maxx\":" . $this->maxx . ", \"maxy\":" . $this->maxy . "}";

        return $json;
    }

    public function hitTest($x, $y)
    {
        $c = 0;
        // do the easy bounding-box test first.
        if (($x < $this->minx) || ($x > $this->maxx) || ($y < $this->miny) || ($y > $this->maxy)) {
            return false;
        }

        // Algorithm from
        // http://www.ecse.rpi.edu/Homepages/wrf/Research/Short_Notes/pnpoly.html#The%20C%20Code
        for ($i = 0, $j = $this->npoints - 1; $i < $this->npoints; $j = $i++) {
            // print "Checking: $i, $j\n";
            $x1 = $this->points[$i][0];
            $y1 = $this->points[$i][1];
            $x2 = $this->points[$j][0];
            $y2 = $this->points[$j][1];

            //  print "($x,$y) vs ($x1,$y1)-($x2,$y2)\n";

            if (((($y1 <= $y) && ($y < $y2)) || (($y2 <= $y) && ($y < $y1))) &&
                ($x < ($x2 - $x1) * ($y - $y1) / ($y2 - $y1) + $x1)
            ) {
                $c = !$c;
            }
        }

        return $c;
    }

    public function __construct($name = "", $href = "", $coords)
    {
        $c = $coords[0];

        $this->name = $name;
        $this->href = $href;
        $this->npoints = count($c) / 2;

        if (intval($this->npoints) != ($this->npoints)) {
            throw new Exception("Odd number of points!");
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
        return "Polygon";
    }
}

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

class HTMLImagemapAreaCircle extends HTMLImagemapArea
{
    public $centx;
    public $centy;
    public $edgex;
    public $edgey;

    public function asHTML()
    {
        $coordstring = join(",", array($this->centx, $this->centy, $this->edgex, $this->edgey));
        return '<area ' . $this->commonHTML() . 'shape="circle" coords="' . $coordstring . '" />';
    }

    public function hitTest($x, $y)
    {
        $radius1 = ($this->edgey - $this->centy) * ($this->edgey - $this->centy)
            + ($this->edgex - $this->centx) * ($this->edgex - $this->centx);

        $radius2 = ($this->centy - $y) * ($this->centy - $y)
            + ($this->centx - $x) * ($this->centx - $x);

        return ($radius2 <= $radius1);
    }

    public function __construct($name = "", $href = "", $coords)
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
        return "Circle";
    }
}

class HTMLImagemap
{
    /** @var HTMLImagemapArea[] $shapes */
    public $shapes;
    public $name;

    public function __construct($name = "")
    {
        $this->Reset();
        $this->name = $name;
    }

    public function reset()
    {
        $this->shapes = array();
        $this->name = "";
    }

    // add an element to the map - takes an array with the info, in a similar way to HTML_QuickForm
    public function addArea($element)
    {
        if (is_object($element) && is_subclass_of($element, 'htmlimagemaparea')) {
            $elementObject = &$element;
        } else {
            $args = func_get_args();
            $className = "HTMLImagemapArea" . $element;
            $elementObject = new $className($args[1], $args[2], array_slice($args, 3));
        }

        $this->shapes[$elementObject->name] = &$elementObject;
    }

    // do a hit-test based on the current map
    // - can be limited to only match elements whose names match the filter
    //   (e.g. pick a building, in a campus map)
    public function hitTest($x, $y, $namefilter = "")
    {
        $preg = '/' . $namefilter . '/';
        foreach ($this->shapes as $shape) {
            if ($shape->hitTest($x, $y)) {
                if (($namefilter == "") || (preg_match($preg, $shape->name))) {
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
            // this USED to be a substring match, but that broke some things
            // and wasn't actually used as one anywhere.
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
        foreach ($this->shapes as $shape) {
            if (($where == "") || (strstr($shape->name, $where) != false)) {
                switch ($which) {
                    case 'href':
                        $shape->href = $what;
                        break;
                    case 'extrahtml':
                        $shape->extrahtml = $what;
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

    public function subJSON($namefilter = "", $reverseorder = false)
    {
        $json = '';

        $preg = '/' . $namefilter . '/';
        foreach ($this->shapes as $shape) {
            if (($namefilter == "") || (preg_match($preg, $shape->name))) {
                if ($reverseorder) {
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
    // (suppose you want some part of your UI to have precedence over another part
    // - the imagemap is checked from top-to-bottom in the HTML)
    // - skipnolinks -> in normal HTML output, we don't need areas for things with no href
    // - reverseorder -> produce the map in the opposite order to the order the items were created
    public function subHTML($namefilter = "", $reverseorder = false, $skipnolinks = false)
    {
        $html = "";

        $n = 0;
        $i = 0;
        foreach ($this->shapes as $shape) {
            $i++;
            if (($namefilter == "") || (strpos($shape->name, $namefilter) === 0)) {
                if ($shape->href != "" || !$skipnolinks || $shape->extrahtml != "") {
                    $n++;
                    if ($reverseorder) {
                        $html = $shape->asHTML() . "\n" . $html;
                    } else {
                        $html .= $shape->asHTML() . "\n";
                    }
                }
            }
        }
        print "$namefilter $n of $i\n";
        return $html;
    }

    public function exactHTML($name = '', $reverseorder = false, $skipnolinks = false)
    {
        $html = '';
        $shape = $this->shapes[$name];

        if (true === isset($shape)) {
            if ((false === $skipnolinks) || ($shape->href !== '')
                || ($shape->extrahtml !== '')
            ) {
                if ($reverseorder === true) {
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
