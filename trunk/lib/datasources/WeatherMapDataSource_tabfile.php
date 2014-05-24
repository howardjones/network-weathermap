<?php
// Sample Pluggable datasource for PHP Weathermap 0.9
// - read a pair of values from a database, and return it

// TARGET dbplug:databasename:username:pass:hostkey

class WeatherMapDataSource_tabfile extends WeatherMapDataSource {

	function Recognise($targetstring)
	{
		if(preg_match("/\.(tsv|txt)$/",$targetstring))
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
		$data[IN] = NULL;
		$data[OUT] = NULL;
		$data_time=0;
		$itemname = $item->name;

		# $matches=0;

		$fd=fopen($targetstring, "r");

		if ($fd)
		{
			while (!feof($fd))
			{
				$buffer=fgets($fd, 4096);
				# strip out any line-endings that have gotten in here
				$buffer=str_replace("\r", "", $buffer);
				$buffer=str_replace("\n", "", $buffer);				

				$parts = explode("\t",$buffer);
				
				if($parts[0] == $itemname) {			   
				    
				    $data[IN] = ($parts[1]=="-" ? NULL : wm_unformat_number($parts[1]) );
				    $data[OUT] = ($parts[2]=="-" ? NULL : wm_unformat_number($parts[2]) );
				    				   
				}
			}
			$stats = stat($targetstring);
			$data_time = $stats['mtime'];
		} else {
		    // some error code to go in here
		    wm_debug ("TabText ReadData: Couldn't open ($targetstring). [WMTABDATA01]\n");
		}
				
		wm_debug ("TabText ReadData: Returning (".($data[IN]===NULL ? 'NULL' : $data[IN]) . "," . ($data[OUT]===NULL ? 'NULL' : $data[OUT]).",$data_time)\n");
	 
		return( array($data[IN], $data[OUT], $data_time) );
	}
}

// vim:ts=4:sw=4:
?>
