<?php
// Sample Pluggable datasource for PHP Weathermap 0.9
// - read a pair of values from a database, and return it

// TARGET dbplug:databasename:username:pass:hostkey

class WeatherMapDataSource_mrtg extends WeatherMapDataSource {

	function Recognise($targetstring)
	{
		if(preg_match("/\.(htm|html)$/",$targetstring,$matches))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	function ReadData($targetstring, &$map, &$item)
	{
		$inbw=-1;
		$outbw=-1;
		$data_time = 0;

		$matches=0;

		$fd=fopen($targetstring, "r");

		if ($fd)
		{
			while (!feof($fd))
			{
				$buffer=fgets($fd, 4096);

				if (preg_match("/<\!-- cuin d (\d+) -->/", $buffer, $matches)) { $inbw=$matches[1] * 8; }

				if (preg_match("/<\!-- cuout d (\d+) -->/", $buffer, $matches)) { $outbw=$matches[1] * 8; }
			}
			fclose($fd);
			$data_time = filemtime($targetstring);
		}
		else {
			// some error code to go in here
			debug ("MRTG ReadData: Couldn't open ($targetstring). \n"); }

			debug ("MRTG ReadData: Returning ($inbw,$outbw,$data_time)\n");

			return ( array($inbw,$outbw,$data_time) );
	}
}

// vim:ts=4:sw=4:
?>
