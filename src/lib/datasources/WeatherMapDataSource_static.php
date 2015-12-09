<?php
// Pluggable datasource for PHP Weathermap 0.9
// - return a static value

// TARGET static:10M
// TARGET static:2M:256K

class WeatherMapDataSource_static extends WeatherMapDataSource
{
    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array(
            '/^static:(\-?\d+\.?\d*[KMGT]?):(\-?\d+\.?\d*[KMGT]?)$/',
            '/^static:(\-?\d+\.?\d*[KMGT]?)$/'
        );

    }

    function ReadData($targetString, &$map, &$mapItem)
    {
        $inbw = null;
        $outbw = null;
        $data_time=0;

        if (preg_match($this->regexpsHandled[0], $targetString, $matches)) {
            $inbw = WMUtility::interpretNumberWithMetricPrefix($matches[1], $map->kilo);
            $outbw = WMUtility::interpretNumberWithMetricPrefix($matches[2], $map->kilo);
            $data_time = time();
        }

        if (preg_match($this->regexpsHandled[1], $targetString, $matches)) {
            $inbw = WMUtility::interpretNumberWithMetricPrefix($matches[1], $map->kilo);
            $outbw = $inbw;
            $data_time = time();
        }
        wm_debug("Static ReadData: Returning ($inbw, $outbw, $data_time)\n");

        return (array($inbw, $outbw, $data_time));
    }
}

// vim:ts=4:sw=4:
