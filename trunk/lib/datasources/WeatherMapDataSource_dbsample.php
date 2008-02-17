<?php
// Sample Pluggable datasource for PHP Weathermap 0.9
// - read a pair of values from a database, and return it

// TARGET dbplug:databasename:username:pass:hostkey

class WeatherMapDataSource_dbsample extends WeatherMapDataSource {

	function Init(&$map)
	{
		if(! function_exists("mysql_real_escape_string") ) return FALSE;
		if(! function_exists("mysql_connect") ) return FALSE;
		
		return(TRUE);
	}
	
	function Recognise($targetstring)
	{
		if(preg_match("/^dbplug:([^:]+)$/",$targetstring,$matches))
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
		$data[IN] = NULL;
		$data[OUT] = NULL;
		$data_time = 0;
		
		if(preg_match("/^dbplug:([^:]+)$/",$targetstring,$matches))
		{
			$database_user = $map->get_hint('dbplug_dbuser');
			$database_pass = $map->get_hint('dbplug_dbpass');
			$database_name = $map->get_hint('dbplug_dbname');
			$database_host = $map->get_hint('dbplug_dbhost');
						
			$key = mysql_real_escape_string($matches[1]);

			$SQL = "select in,out from table where host=$key LIMIT 1";
			if(mysql_connect($database_host,$database_user,$database_pass))
			{
				if(mysql_select_db($database_name))
				{
					$result = mysql_query($SQL);
					if (!$result)
					{
					    warn("dbsample ReadData: Invalid query: " . mysql_error()."\n");
					}
					else
					{
						$row = mysql_fetch_assoc($result);
						$data[IN] = $row['in'];
						$data[OUT] = $row['out'];
					}
				}
				else
				{
					warn("dbsample ReadData: failed to select database: ".mysql_error()."\n");
				}
			}
			else
			{
				warn("dbsample ReadData: failed to connect to database server: ".mysql_error()."\n");
			}
			
			$data_time = now();
		}
		
		
		debug ("RRD ReadData: Returning (".($data[IN]===NULL?'NULL':$data[IN]).",".($data[OUT]===NULL?'NULL':$data[IN]).",$data_time)\n");
		
		return( array($data[IN], $data[OUT], $data_time) );
	}
}

// vim:ts=4:sw=4:
?>
