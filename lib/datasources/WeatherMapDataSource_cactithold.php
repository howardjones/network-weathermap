<?php

class WeatherMapDataSource_cactithold extends WeatherMapDataSource {

	function Init(&$map)
	{
		if($map->context == 'cacti')
		{   
			if( function_exists('db_fetch_row') )
			{
				return(TRUE);
			}
			else
			{
				debug('ReadData CactiTHold: Cacti database library not found.\n');
			}
		}
		else
		{
			debug("ReadData CactiTHold: Can only run from Cacti environment.\n");
		}

		return(FALSE);
	}

	function Recognise($targetstring)
	{
		if(preg_match("/^cactithold:(\d+)$/",$targetstring,$matches))
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

		$inbw = -1;
		$outbw = -1;
		$data_time = 0;

		if(preg_match("/^cactithold:(\d+)$/",$targetstring,$matches))
		{
			$cacti_id = intval($matches[1]);

			$SQL = "select * from host where id=$cacti_id";
			// 0=disabled
			// 1=down
			// 2=recovering
			// 3=up

			$state = -1;
			$result = db_fetch_row($SQL);
			if(isset($result))
			{
				// create a note, which can be used in icon filenames or labels more nicely
				if($result['status'] == 1) { $state = 1; $statename = 'down'; }
				if($result['status'] == 2) { $state = 2; $statename = 'recovering'; }
				if($result['status'] == 3) { $state = 3; $statename = 'up'; }
				if($result['disabled'])  { $state = 0; $statename = 'disabled'; }

				$inbw = $state;
				$outbw = $state;
				$item->add_note("state",$statename);
				$item->add_note("cacti_description",$result['description']);
				
				$item->add_note("cacti_hostname",$result['hostname']);
				$item->add_note("cacti_curtime",$result['cur_time']);
				$item->add_note("cacti_avgtime",$result['avg_time']);
				$item->add_note("cacti_mintime",$result['min_time']);
				$item->add_note("cacti_maxtime",$result['max_time']);
				$item->add_note("cacti_availability",$result['availability']);
				
				$item->add_note("cacti_faildate",$result['status_fail_date']);
				$item->add_note("cacti_recdate",$result['status_rec_date']);
			}
		}

		debug ("CactiTHold ReadData: Returning ($inbw,$outbw,$data_time)\n");

		return( array($inbw, $outbw, $data_time) );
	}
}


// vim:ts=4:sw=4:
?>
