<?php
// Sample Pluggable datasource for PHP Weathermap 0.9
// - read a pair of values from a database, and return it

// TARGET dbplug:databasename:username:pass:hostkey

class WeatherMapDataSource_tabfile extends WeatherMapDataSource {

	function Recognise($targetstring)
	{
		if(preg_match("/\.(tsv|txt)$/",$targetstring,$matches))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	// function ReadData($targetstring, $configline, $itemtype, $itemname, $map)
	function ReadData($targetstring, &$map, &$item)
	{
		$inbw=-1;
		$outbw=-1;
		$data_time=0;
		$itemname = $item->name;

		$matches=0;

		$fd=fopen($targetstring, "r");

		if ($fd)
		{
			while (!feof($fd))
			{
				$buffer=fgets($fd, 4096);
				# strip out any Windows line-endings that have gotten in here
				$buffer=str_replace("\r", "", $buffer);

				if (preg_match("/^$itemname\t(\d+\.?\d*[KMGT]*)\t(\d+\.?\d*[KMGT]*)/", $buffer, $matches))
				{
					$inbw=unformat_number($matches[1]);
					$outbw=unformat_number($matches[2]);
				}
			}
			$stats = stat($targetstring);
			$data_time = $stats['mtime'];
		}
		else {
			// some error code to go in here
			debug ("TabText ReadData: Couldn't open ($targetstring). \n"); }

			debug ("TabText ReadData: Returning ($inbw,$outbw,$data_time)\n");

			return ( array($inbw,$outbw,$data_time) );
	}
}

// vim:ts=4:sw=4:
?>
