<?php
// Sample Pluggable datasource for PHP Weathermap 0.9
// - read a pair of values from a database, and return it

// TARGET dbplug:key

// Expects to find SET variables for the database access details
//     dbplug_dbuser - mysql username
//     dbplug_dbpass - mysql password
//     dbplug_dbname - mysql database name
//     dbplug_dbhost - mysql host

// Intended as a demo, but should be useful for something, too

class WeatherMapDataSource_dbplug extends WeatherMapDataSource {

	function Init(&$map)
	{
		if (! function_exists("mysql_real_escape_string") ) {
            return false;
        }

		if (! function_exists("mysql_connect") ) {
            return false;
        }
		
		return(true);
	}
	
	function Recognise($targetstring)
	{
		if (preg_match("/^dbplug:([^:]+)$/", $targetstring))
		{
			return true;
		} else {
			return false;
		}
	}

	function ReadData($targetstring, &$map, &$item)
	{
		$data[IN] = null;
		$data[OUT] = null;
		$data_time = 0;
		
		if (preg_match("/^dbplug:([^:]+)$/",$targetstring,$matches)) {
			$database_user = $map->get_hint('dbplug_dbuser');
			$database_pass = $map->get_hint('dbplug_dbpass');
			$database_name = $map->get_hint('dbplug_dbname');
			$database_host = $map->get_hint('dbplug_dbhost');
						
			$key = mysql_real_escape_string($matches[1]);

			// This is the one line you will certainly need to change
			$SQL = "select in,out from table where host=$key LIMIT 1";
			
			if (mysql_connect($database_host, $database_user, $database_pass)) {
                if (mysql_select_db($database_name)) {
                    $result = mysql_query($SQL);
					if (!$result) {
                        wm_warn("dbplug ReadData: Invalid query: " . mysql_error()."\n");
					} else {
                        $row = mysql_fetch_assoc($result);
						$data[IN] = $row['in'];
						$data[OUT] = $row['out'];
					}
				} else {
                    wm_warn("dbplug ReadData: failed to select database: ".mysql_error()."\n");
				}
			} else {
                wm_warn("dbplug ReadData: failed to connect to database server: ".mysql_error()."\n");
			}
			
			$data_time = now();
		}
		
		
		wm_debug ("RRD ReadData dbplug: Returning (".($data[IN]===null?'null':$data[IN]).",".($data[OUT]===null?'null':$data[IN]).", $data_time)\n");
		
		return( array($data[IN], $data[OUT], $data_time) );
	}
}

// vim:ts=4:sw=4:
