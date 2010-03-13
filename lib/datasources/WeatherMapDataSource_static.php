<?php
// Pluggable datasource for PHP Weathermap 0.9
// - return a static value

// TARGET static:10M
// TARGET static:2M:256K

class WeatherMapDataSource_static extends WeatherMapDataSource
{
    function Recognise($targetstring)
    {
        if (preg_match("/^static:(\-?\d+\.?\d*[KMGT]?):(\-?\d+\.?\d*[KMGT]?)$/",
            $targetstring, $matches)
            || preg_match("/^static:(\-?\d+\.?\d*[KMGT]?)$/", $targetstring, $matches)) {
            return true;
        } else {
            return false;
        }
    }

    function ReadData($targetstring, &$map, &$item)
    {
        $inbw = null;
        $outbw = null;
        $data_time = 0;

        if (preg_match("/^static:(\-?\d+\.?\d*[KMGT]*):(\-?\d+\.?\d*[KMGT]*)$/",
            $targetstring, $matches)) {
            $inbw = unformat_number($matches[1]);
            $outbw = unformat_number($matches[2]);
            $data_time = time();
        }

        if (preg_match("/^static:(\-?\d+\.?\d*[KMGT]*)$/", $targetstring, $matches)) {
            $inbw = unformat_number($matches[1]);
            $outbw = unformat_number($matches[1]);
            $data_time = time();
        }
        
        debug( sprintf("Static ReadData: Returning (%s, %s, %s)\n",
		        string_or_null($data[IN]),
		        string_or_null($data[OUT]),
		        $data_time
        	));

        return (array (
            $inbw,
            $outbw,
            $data_time
        ));
    }
}

// vim:ts=4:sw=4:
?>