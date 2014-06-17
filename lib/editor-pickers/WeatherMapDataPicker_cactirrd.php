<?php

class WeatherMapDataPicker_cactirrd extends WeatherMapDataPicker
{
    function getInfo()
    {
        return(array("nlevels"=>4, levels=>array("host","ds_template","instance","ds_names")));
    }

    function initialise($config)
    {

    }
}