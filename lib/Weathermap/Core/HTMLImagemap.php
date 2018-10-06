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

/**
 * The base class that contains the ImagemapArea objects, and produces the map HTML. (UNUSED?)
 *
 * @package Weathermap\Core
 */
class HTMLImagemap
{
    /** @var HTMLImagemapArea[] $shapes */
    public $shapes;
    public $name;

    public function __construct($name = '')
    {
        $this->Reset();
        $this->name = $name;
    }

    public function reset()
    {
        $this->shapes = array();
        $this->name = '';
    }

    // add an element to the map - takes an array with the info, in a similar way to HTML_QuickForm
    public function addArea($element)
    {
        if (is_object($element) && is_subclass_of($element, 'Weathermap\Core\HTMLImagemapArea')) {
            $elementObject = &$element;
        } else {
            $args = func_get_args();
            $className = 'Weathermap\Core\HTMLImagemapArea' . $element;
            $elementObject = new $className(array_slice($args, 3), $args[1], $args[2]);
        }

        $this->shapes[$elementObject->name] = &$elementObject;
    }

    // do a hit-test based on the current map
    // - can be limited to only match elements whose names match the filter
    //   (e.g. pick a building, in a campus map)
    public function hitTest($x, $y, $namefilter = '')
    {
        $preg = '/' . $namefilter . '/';
        foreach ($this->shapes as $shape) {
            if ($shape->hitTest($x, $y) &&
                ($namefilter == '' || preg_match($preg, $shape->name))) {
                return $shape->name;
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
            if (($where == '') || (strstr($shape->name, $where) != false)) {
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

    public function getByName($name)
    {
        return $this->shapes[$name];
    }

    public function getBySubstring($nameFilter, $reverseOrder = false)
    {
        $result = array();

        foreach ($this->shapes as $shape) {
            if (($nameFilter == '') || (strpos($shape->name, $nameFilter) === 0)) {
                if ($reverseOrder) {
                    array_unshift($result, $shape);
                } else {
                    array_push($result, $shape);
                }
            }
        }

        return $result;
    }

// Return the imagemap as an HTML client-side imagemap for inclusion in a page
    public function asHTML()
    {
        $html = '<map';
        if ($this->name != '') {
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

    public function exactHTML($name = '', $skipNoLinks = false)
    {
        $html = '';

        if (array_key_exists($name, $this->shapes)) {
            return $html;
        }

        $shape = $this->shapes[$name];

        if (((false === $skipNoLinks) || $shape->hasLinks())) {
            $html = $shape->asHTML();
        }

        return $html;
    }
}
// vim:ts=4:sw=4:
