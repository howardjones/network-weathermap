<?php
// Pluggable datasource for PHP Weathermap 0.9
// - return a live ping result

// TARGET fping:ipaddress
// TARGET fping:hostname

class WeatherMapDataSource_fping extends WeatherMapDataSource {

	var $addresscache = array();
	var $donepings = FALSE;
	var $results = array();

	function Init(&$map)
	{
		return(TRUE);
	}

	// this function will get called for every datasource, even if we replied FALSE to Init.
	// (so that we can warn the user that it *would* have worked, if only the plugin could run)
	// SO... don't do anything in here that relies on the things that Init looked for, because they might not exist!
	function Recognise($targetstring)
	{
		if(preg_match("/^fping:(\S+)$/",$targetstring,$matches))
		{
			// save the address. This way, we can do ONE fping call for all the pings in the map.
			// fping does it all in parallel, so 10 hosts takes the same time as 1
			$this->addresscache[]=$matches[1];
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	function ReadData($targetstring, &$map, &$item)
	{
		$data[IN] = NULL;
		$data[OUT] = NULL;
		$data_time = 0;

		debug("-------------------------\n");
		print_r($this->addresscache);
		debug("-------------------------\n");
		
		if(preg_match("/^fping:(\S+)$/",$targetstring,$matches))
		{
# fping -t100 -r1 -u -C 5 -i10 -q www.google.com
# www.google.com : 13.94 13.97 13.96 14.11 18.34

		}

		debug ("FPing ReadData: Returning (".($data[IN]===NULL?'NULL':$data[IN]).",".($data[OUT]===NULL?'NULL':$data[IN]).",$data_time)\n");
		
		return( array($data[IN], $data[OUT], $data_time) );
	}
}

// vim:ts=4:sw=4:
?>
