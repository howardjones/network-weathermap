<?php
// Sample Pluggable datasource for PHP Weathermap 0.9
// - read a pair of values from a database, and return it

// TARGET dbplug:databasename:username:pass:hostkey

class WeatherMapDataSource_dbsample extends WeatherMapDataSource
{
    function Init(&$map)
    {
        if (false === function_exists('mysql_real_escape_string')) {
            return false;
        }

        if (false === function_exists('mysql_connect')) {
            return false;
        }

        return (true);
    }

    function Recognise($targetstring)
    {
        if (1 === preg_match('/^dbplug:([^:]+)$/', $targetstring, $matches)) {
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

        if (1 === preg_match('/^dbplug:([^:]+)$/', $targetstring, $matches)) {
            $database_user = $map->get_hint('dbplug_dbuser');
            $database_pass = $map->get_hint('dbplug_dbpass');
            $database_name = $map->get_hint('dbplug_dbname');
            $database_host = $map->get_hint('dbplug_dbhost');

            $key = mysql_real_escape_string($matches[1]);

            $SQL = sprintf('select in,out from table where host=%s LIMIT 1', $key);

            if (false !== mysql_connect($database_host, $database_user, $database_pass)) {
                            
                if (true === mysql_select_db($database_name)) {
                    
                    $result = mysql_query($SQL);

                    if (false === $result) {
                        warn('dbsample ReadData: Invalid query: ' . mysql_error() . "\n");
                    } else {
                        $row = mysql_fetch_assoc($result);
                        $data[IN] = $row['in'];
                        $data[OUT] = $row['out'];
                    }
                } else {
                    warn('dbsample ReadData: failed to select database: ' . mysql_error()
                        . "\n");
                }
            } else {
                warn('dbsample ReadData: failed to connect to database server: '
                    . mysql_error() . "\n");
            }

            $data_time = now();
        }

        debug( sprintf("dbsample ReadData: Returning (%s, %s, %s)\n",
		        string_or_null($data[IN]),
		        string_or_null($data[OUT]),
		        $data_time
        	));

        return (array (
            $data[IN],
            $data[OUT],
            $data_time
        ));
    }
}

// vim:ts=4:sw=4:
?>