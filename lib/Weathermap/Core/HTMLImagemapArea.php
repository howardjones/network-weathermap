<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 23/09/17
 * Time: 11:14
 */

namespace Weathermap\Core;

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
