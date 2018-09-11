<?php

namespace Weathermap\Core;

/**
 * Base class for the other ImagemapArea classes - just the common stuff
 * @package Weathermap\Core
 */
class HTMLImagemapArea
{
    public $href;
    public $name;
    public $id;
    public $alt;
    public $z;
    public $extrahtml;
    public $info;

    public function __construct()
    {
        $this->info = array();
    }

    protected function commonHTML()
    {
        $h = '';
        if ($this->name != '') {
            // $h .= " alt=\"".$this->name."\" ";
            $h .= 'id="' . $this->name . '" ';
        }
        if ($this->href != '') {
            $h .= 'href="' . $this->href . '" ';
        } else {
            $h .= 'nohref ';
        }
        if ($this->extrahtml != '') {
            $h .= $this->extrahtml . ' ';
        }
        return $h;
    }
}
