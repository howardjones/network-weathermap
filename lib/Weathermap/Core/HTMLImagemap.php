<?php
namespace Weathermap\Core;

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
        if (is_object($element) && is_subclass_of($element, 'Weathermap\Core\HTMLImagemapArea')) {
            $elementObject = &$element;
        } else {
            $args = func_get_args();
            $className = 'Weathermap\Core\HTMLImagemapArea' . $element;
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
