<?php
// Pluggable datasource for PHP Weathermap 0.9
// - return a static value

// TARGET static:10M
// TARGET static:2M:256K

class WeatherMapDataSource_static extends WeatherMapDataSource {

	function Recognise($targetstring)
	{
		if ( preg_match("/^static:(\-?\d+\.?\d*[KMGT]?):(\-?\d+\.?\d*[KMGT]?)$/", $targetstring) ||
			preg_match("/^static:(\-?\d+\.?\d*[KMGT]?)$/", $targetstring) ) {
			return true;
		} else {
			return false;
		}
	}

	function ReadData($targetstring, &$map, &$item)
	{
		$inbw = null;
		$outbw = null;
		$data_time=0;

		if (preg_match("/^static:(\-?\d+\.?\d*[KMGT]*):(\-?\d+\.?\d*[KMGT]*)$/", $targetstring, $matches)) {
			$inbw = wmInterpretNumberWithMetricPrefix($matches[1], $map->kilo);
			$outbw = wmInterpretNumberWithMetricPrefix($matches[2], $map->kilo);
			$data_time = time();
		}

		if (preg_match("/^static:(\-?\d+\.?\d*[KMGT]*)$/", $targetstring, $matches))
		{
			$inbw = wmInterpretNumberWithMetricPrefix($matches[1], $map->kilo);
			$outbw = $inbw;
			$data_time = time();
		}
		wm_debug ("Static ReadData: Returning ($inbw, $outbw, $data_time)\n");

		return ( array($inbw, $outbw, $data_time) );
	}
}

// vim:ts=4:sw=4:
