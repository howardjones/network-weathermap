<?php

// Cacti thold/monitor DS plugin
//   Can read state of Thresholds from the THold Cacti plugin
//   and also overall host state, in the style of the Monitor plugin (it doesn't depend on that plugin to do this)
//
// It DOES depend on THold though, obviously!
//
// Possible TARGETs:
//
//  cactithold:234
//  (internal thold id - returns 0 for OK, and 1 for breach)
//
//  cactithold:12:444
//  (the two IDs seen in thold URLs- also returns 0 for OK, and 1 for breach)
//
//  cactimonitor:22
//  (cacti hostid - returns host state (0-3) or 4 for failing some thresholds)
//  also sets all the same variables as cactihost: would, and a new possible 'state' name of 'tholdbreached'
//
// Original development for this plugin was paid for by
//    Stellar Consulting

class WeatherMapDataSource_cactithold extends WeatherMapDataSource {

	function Init(&$map)
	{
		global $plugins;
		
		if($map->context == 'cacti')
		{   
			if( !function_exists('db_fetch_row') )
			{
				debug('ReadData CactiTHold: Cacti database library not found. [THOLD001]\n');
				return(FALSE);
			}
			
			if( !isset($plugins) || !in_array('thold',$plugins))
			{
				debug('ReadData CactiTHold: THold plugin not enabled. [THOLD002]\n');
				return(FALSE);
			}
			
			$sql = "show tables";
			$result = db_fetch_assoc($sql) or die (mysql_error());
			$tables = array();
			
			foreach($result as $index => $arr) {
				foreach ($arr as $t) {
					$tables[] = $t;
				}
			}
			
			if( !in_array('thold_data', $tables) )
			{
				debug('ReadData CactiTHold: thold_data database table not found. [THOLD003]\n');
				return(FALSE);
			}			
			
			return(TRUE);			
		}
		else
		{
			debug("ReadData CactiTHold: Can only run from Cacti environment. [THOLD004]\n");
		}

		return(FALSE);
	}

	function Recognise($targetstring)
	{
		if(preg_match("/^cacti(thold|monitor):(\d+)$/",$targetstring,$matches))
		{
			return TRUE;
		}
		elseif(preg_match("/^cactithold:(\d+):(\d+)$/",$targetstring,$matches))
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

		if(preg_match("/^cactithold:(\d+):(\d+)$/",$targetstring,$matches))
		{
			// Returns 0 if threshold is not breached, 1 if it is.
			// use target aggregation to build these up into a 'badness' percentage
			// takes the same two values that are visible in thold's own URLs (the actual thold ID isn't shown anywhere)
			
			$rra_id = intval($matches[1]);
			$data_id = intval($matches[2]);
			
			$SQL2 = "select thold_alert from thold_data where rra_id=$rra_id and data_id=$data_id and thold_enabled='on'";
			$result = db_fetch_row($SQL2);
			if(isset($result))
			{
				if($result['thold_alert'] > 0) { $inbw=1; }
				else { $inbw = 0; }
				$outbw = 0;
			}
		}
		elseif(preg_match("/^cacti(thold|monitor):(\d+)$/",$targetstring,$matches))
		{
			$type = $matches[1];
			$id = intval($matches[2]);

			if($type=='thold')
			{
				// VERY simple. Returns 0 if threshold is not breached, 1 if it is.
				// use target aggregation to build these up into a 'badness' percentage
				$SQL2 = "select thold_alert from thold_data where id=$id and thold_enabled='on'";
				$result = db_fetch_row($SQL2);
				if(isset($result))
				{
					if($result['thold_alert'] > 0) { $inbw=1; }
					else { $inbw = 0; }
					$outbw = 0;
				}
			}
			
			if($type=='monitor')
			{
				$SQL = "select * from host where id=$id";
				
				// 0=disabled
				// 1=down
				// 2=recovering
				// 3=up
				// 4=tholdbreached
	
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
					$outbw = 0;
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
				
				$numthresh = 0;
				$numfailing = 0;
				$SQL2 = "select thold_alert from thold_data where host_id=$id and thold_enabled='on'";
				# $result = db_fetch_row($SQL2);
				$queryrows = db_fetch_assoc($SQL2);
				if( is_array($queryrows) )
				{
					foreach ($queryrows as $th) {					
						$numthresh++;
						if($th['thold_alert'] > 0) 
						{
							debug("CactiTHold ReadData: Seen threshold failing for host $id\n");
							$numfailing++;
						}
					}
				}
				else
				{
					debug("CactiTHold ReadData: Failed to get thold info for host $id\n");
				}
				
				debug("CactiTHold ReadData: Checked $numthresh and found $numfailing failing\n");
				
				if( ($numfailing > 0) && ($numthresh > 0) && ($state==3) )
				{
					$state = 4;
					$statename = "tholdbreached";
					$item->add_note("state",$statename);
					$item->add_note("thold_failcount",$numfailing);
					$item->add_note("thold_failpercent",($numfailing/$numthresh)*100);
					$inbw = $state;
					$outbw = $numfailing;
					debug("CactiTHold ReadData: State is 4\n");
				}
				elseif( $numthresh>0 )
				{
					$item->add_note("thold_failcount",0);
					$item->add_note("thold_failpercent",0);
					debug("CactiTHold ReadData: Leaving state as $state\n");
				}
			}		
		}

		debug ("CactiTHold ReadData: Returning ($inbw,$outbw,$data_time)\n");

		return( array($inbw, $outbw, $data_time) );
	}
}


// vim:ts=4:sw=4:
?>
