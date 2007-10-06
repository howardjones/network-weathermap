<?php
// Sample Pluggable datasource for PHP Weathermap 0.9
// - read a pair of values from a database, and return it

// TARGET dbplug:databasename:username:pass:hostkey

class WeatherMapDataSource_dbsample extends WeatherMapDataSource {

	function Recognise($targetstring)
	{
		if(preg_match("/^dbplug:([^:]+):([^:]+):([^:]+):([^:]+)$/",$targetstring,$matches))
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
		if(preg_match("/^dbplug:([^:]+):([^:]+):([^:]+):([^:]+)$/",$targetstring,$matches))
		{
			$database = $matches[0];
			$db_user = $matches[1];
			$db_pass = $matches[2];
			$key = mysql_real_escape_string($matches[3]);

			$SQL = "select in,out from table where host=$key";
		}
		else
		{
			return ( array(-1,-1) );
		}
	}
}

// vim:ts=4:sw=4:
?>
