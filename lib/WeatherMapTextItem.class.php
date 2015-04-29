<?php

/**
 * Class WeatherMapTextItem replaces the random attributes for Title, Timestamp, min/max timestamp in the
 * main Weathermap class. Now they can all be drawn like a node or link with a draw method.
 */

class WeatherMapTextItem extends WeatherMapItem
{
    var $font;
    var $position;
    var $textColour;
    var $text;

    public function __construct()
    {
        parent::__construct();

        $this->font = 3;
        $this->position = new WMPoint(-1000, -1000);
        $this->textColour = new WMColour(0, 0, 0);
        $this->text = "";
    }

    public function my_type()
    {
        return "TEXT";
    }
}
