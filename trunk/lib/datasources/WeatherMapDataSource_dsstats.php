<?php
class WeatherMapDataSource_dsstats extends WeatherMapDataSource {

	function Init(&$map)
	{
		global $config;
		if($map->context=='cacti')
		{
			return(TRUE);
		}

		return(FALSE);
	}

# dsstats:<datatype>:<local_data_id>:<rrd_name_in>:<rrd_name_out>

	function Recognise($targetstring)
	{
		if(preg_match("/^dsstats:([a-z]+):(\d+):([\-a-zA-Z0-9_]+):([\-a-zA-Z0-9_]+)$/",$targetstring,$matches))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	// Actually read data from a data source, and return it
	// returns a 3-part array (invalue, outvalue and datavalid time_t)
	// invalue and outvalue should be -1,-1 if there is no valid data
	// data_time is intended to allow more informed graphing in the future
	function ReadData($targetstring, &$map, &$item)
	{
		global $config;
		
		$dsnames[IN] = "traffic_in";
		$dsnames[OUT] = "traffic_out";
		$data[IN] = NULL;
		$data[OUT] = NULL;

		$inbw = NULL;
		$outbw = NULL;
		$data_time = 0;

                if(preg_match("/^dsstats:([a-z]+):(\d+):([\-a-zA-Z0-9_]+):([\-a-zA-Z0-9_]+)$/",$targetstring,$matches))
		{
			$dsnames[IN] = $matches[3];
			$dsnames[OUT] = $matches[4];
			$datatype = $matches[1];
			$local_data_id = $matches[2];

		$table = "";

		if( substr($datatype,0,5) == "daily") $table = "data_source_stats_daily";
		if( substr($datatype,0,6) == "weekly") $table = "data_source_stats_weekly";
		if( substr($datatype,0,7) == "monthly") $table = "data_source_stats_monthly";
		if( substr($datatype,0,6) == "hourly") $table = "data_source_stats_hourly";
		if( substr($datatype,0,6) == "yearly") $table = "data_source_stats_yearly";

		if( substr($datatype,-7) == "average" ) $field = "average";
		if( substr($datatype,-4) == "peak" ) $field = "peak";


		if($datatype == "last")
		{
			$field = "calculated";
			$table = "data_source_stats_hourly_last";
		}

		if($table != "" and $field != "")
		{

		$SQL = sprintf("select rrd_name, %s as result from %s where local_data_id=%d and (rrd_name='%s' or rrd_name='%s')", $field, $table, $local_data_id, mysql_escape_string($dsnames[IN]), mysql_escape_string($dsnames[OUT]));
		$results = db_fetch_assoc($SQL);
		if(sizeof($results)>0)
		{
			foreach ($results as $result)
                        {
				foreach ( array(IN,OUT) as $dir)
				{
					if($dsnames[$dir] == $result['rrd_name'])
					{
						$data[$dir] = $result['result'];		
					}
				}
                        }
		}

		}
		}
				
		debug ("DSStats ReadData: Returning (".($data[IN]===NULL?'NULL':$data[IN]).",".($data[OUT]===NULL?'NULL':$data[OUT]).",$data_time)\n");
		
		return( array($data[IN], $data[OUT], $data_time) );
	}
}

// vim:ts=4:sw=4:
?>
