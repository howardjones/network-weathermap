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
				wm_debug("ReadData CactiTHold: Cacti database library not found. [THOLD001]\n");
				return(FALSE);
			}

$thold_present = false;

                        if (function_exists("api_plugin_is_enabled")) {
                                if (api_plugin_is_enabled('thold')) {
                                        $thold_present = true;
                                }
                        }

                        if ( isset($plugins) && in_array('thold',$plugins)) {
                                $thold_present = true;
                        }

                        if ( !$thold_present) {
                                wm_debug("ReadData CactiTHold: THold plugin not enabled. [THOLD002]\n");
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
				wm_debug('ReadData CactiTHold: thold_data database table not found. [THOLD003]\n');
				return(FALSE);
			}			
			
			return(TRUE);			
		}
		else
		{
			wm_debug("ReadData CactiTHold: Can only run from Cacti environment. [THOLD004]\n");
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

		$data[IN] = NULL;
		$data[OUT] = NULL;
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
				if($result['thold_alert'] > 0) { $data[IN]=1; }
				else { $data[IN] = 0; }
				$data[OUT] = 0;
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
					if($result['thold_alert'] > 0) { $data[IN]=1; }
					else { $data[IN] = 0; }
					$data[OUT] = 0;
				}
			}
			
			if($type=='monitor')
			{
				wm_debug("CactiTHold ReadData: Getting cacti basic state for host $id\n");
				$SQL = "select * from host where id=$id";
				
				// 0=disabled
				// 1=down
				// 2=recovering
				// 3=up
				// 4=tholdbreached
	
				$state = -1;
				$statename = '';
				$result = db_fetch_row($SQL);
				if(isset($result))
				{
					// create a note, which can be used in icon filenames or labels more nicely
					if($result['status'] == 1) { $state = 1; $statename = 'down'; }
					if($result['status'] == 2) { $state = 2; $statename = 'recovering'; }
					if($result['status'] == 3) { $state = 3; $statename = 'up'; }
					if($result['disabled'])  { $state = 0; $statename = 'disabled'; }
	
					$data[IN] = $state;
					$data[OUT] = 0;
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
				wm_debug("CactiTHold ReadData: Basic state for host $id is $state/$statename\n");
				
				wm_debug("CactiTHold ReadData: Checking threshold states for host $id\n");
				$numthresh = 0;
				$numfailing = 0;
				$SQL2 = "select rra_id, data_id, thold_alert from thold_data,data_local where thold_data.rra_id=data_local.id and data_local.host_id=$id and thold_enabled='on'";
				# $result = db_fetch_row($SQL2);
				$queryrows = db_fetch_assoc($SQL2);
				if( is_array($queryrows) )
				{
					foreach ($queryrows as $th) {					
						$desc = $th['rra_id']."/".$th['data_id'];
						$v = $th['thold_alert'];
						$numthresh++;
						if(intval($th['thold_alert']) > 0) 
						{
							wm_debug("CactiTHold ReadData: Seen threshold $desc failing ($v)for host $id\n");
							$numfailing++;
						}
						else
						{
							wm_debug("CactiTHold ReadData: Seen threshold $desc OK ($v) for host $id\n");
						}
					}
				}
				else
				{
					wm_debug("CactiTHold ReadData: Failed to get thold info for host $id\n");
				}
				
				wm_debug("CactiTHold ReadData: Checked $numthresh and found $numfailing failing\n");
				
				if( ($numfailing > 0) && ($numthresh > 0) && ($state==3) )
				{
					$state = 4;
					$statename = "tholdbreached";
					$item->add_note("state",$statename);
					$item->add_note("thold_failcount",$numfailing);
					$item->add_note("thold_failpercent",($numfailing/$numthresh)*100);
					$data[IN] = $state;
					$data[OUT] = $numfailing;
					wm_debug("CactiTHold ReadData: State is $state/$statename\n");
				}
				elseif( $numthresh>0 )
				{
					$item->add_note("thold_failcount",0);
					$item->add_note("thold_failpercent",0);
					wm_debug("CactiTHold ReadData: Leaving state as $state\n");
				}
			}		
		}

		wm_debug ("CactiTHold ReadData: Returning (".($data[IN]===NULL?'NULL':$data[IN]).",".($data[OUT]===NULL?'NULL':$data[OUT]).",$data_time)\n");

		return( array($data[IN], $data[OUT], $data_time) );
	}
}


// vim:ts=4:sw=4:
