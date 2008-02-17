<?php
// RRDtool datasource plugin.
//     gauge:filename.rrd:ds_in:ds_out
//     filename.rrd:ds_in:ds_out
//     filename.rrd:ds_in:ds_out
//
class WeatherMapDataSource_rrd extends WeatherMapDataSource {

	function Init(&$map)
	{
		#if (extension_loaded('RRDTool')) // fetch the values via the RRDtool Extension
		#{
	#		debug("RRD DS: Using RRDTool php extension.\n");
#			return(TRUE);
#		}
#		else
#		{
			if (file_exists($map->rrdtool)) {
				if((function_exists('is_executable')) && (!is_executable($map->rrdtool)))
				{
					warn("RRD DS: RRDTool exists but is not executable? [WMRRD01]\n");
					return(FALSE);
				}
				$map->rrdtool_check="FOUND";
				return(TRUE); 
			}
			// normally, DS plugins shouldn't really pollute the logs
			// this particular one is important to most users though...
			if($map->context=='cli')
			{
				warn("RRD DS: Can't find RRDTOOL. Check line 29 of the 'weathermap' script.\nRRD-based TARGETs will fail. [WMRRD02]\n");
			}
			if($map->context=='cacti')
			{    // unlikely to ever occur
				warn("RRD DS: Can't find RRDTOOL. Check your Cacti config. [WMRRD03]\n");
			}
#		}

		return(FALSE);
	}

	function Recognise($targetstring)
	{
		if(preg_match("/^(.*\.rrd):([\-a-zA-Z0-9_]+):([\-a-zA-Z0-9_]+)$/",$targetstring,$matches))
		{
			return TRUE;
		}
		elseif(preg_match("/^(.*\.rrd)$/",$targetstring,$matches))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	function wmrrd_read_from_poller_output($rrdfile,$cf,$start,$end,$dsnames, &$data, &$map, &$data_time)
	{
		global $config;
		
		if(isset($config))
		{
			debug("RRD ReadData: poller_output style\n");

			// force it to be a complete path first,
			// then take away the cacti bit, to get the appropriate path for the table
			$db_rrdname = realpath($rrdfile);
			$db_rrdname = str_replace($config["base_path"]."/rra","<path_rra>",$db_rrdname);
			debug("******************************************************************\nChecking weathermap_data\n");
			foreach (array(IN,OUT) as $dir)
			{
				debug("RRD ReadData: poller_output - looking for $dir value\n");
				if($dsnames[$dir] != '-')
				{
					debug("RRD ReadData: poller_output - DS name is ".$dsnames[$dir]."\n");
					$SQL = "select * from weathermap_data where rrdfile='".mysql_real_escape_string($db_rrdname)."' and data_source_name='".mysql_real_escape_string($dsnames[$dir])."'";
					$SQLins = "insert into weathermap_data (rrdfile, data_source_name,sequence) values ('".mysql_real_escape_string($db_rrdname)."','".mysql_real_escape_string($dsnames[$dir])."', 0)";
					$SQLcheck = "select data_template_data.local_data_id from data_template_data,data_template_rrd where data_template_data.local_data_id=data_template_rrd.local_data_id and data_template_data.data_source_path='".mysql_real_escape_string($db_rrdname)."' and data_template_rrd.data_source_name='".mysql_real_escape_string($dsnames[$dir])."'";
					$SQLvalid = "select data_template_rrd.data_source_name from data_template_data,data_template_rrd where data_template_data.local_data_id=data_template_rrd.local_data_id and data_template_data.data_source_path='".mysql_real_escape_string($db_rrdname)."'";
					$worst_time = time() - 8*60;
					$result = db_fetch_row($SQL);
					if(!isset($result['id']))
					{
						debug("RRD ReadData: poller_output - Adding new weathermap_data row for $db_rrdname:".$dsnames[$dir]."\n");
						$result = db_fetch_row($SQLcheck);
						if(!isset($result['local_data_id']))
						{
							$fields = array();
							$results = db_fetch_assoc($SQLvalid);
							foreach ($results as $result)
							{
								$fields[] = $result['data_source_name'];
							}
							if(count($fields) > 0)
							{
								warn("RRD ReadData: poller_output: ".$dsnames[$dir]." is not a valid DS name for $db_rrdname - valid names are: ".join(", ",$fields)."\n");
							}
							else
							{
								warn("RRD ReadData: poller_output: ".$dsnames[$dir]." is not a valid RRD filename within this Cacti install.\n");
							}
						}
						else
						{
							db_execute($SQLins);
						}
					}
					else
					{
						debug("RRD ReadData: poller_output - found weathermap_data row\n");
						// if the result is valid, then use it
						if( ($result['sequence'] > 2) && ( $result['last_time'] > $worst_time) )
						{
							$data[$dir] = $result['last_calc'];
							$data_time = $result['last_time'];
							debug("RRD ReadData: poller_output - data looks valid\n");
						}
						else
						{
							$data[$dir] = 0;
							debug("RRD ReadData: poller_output - data is either too old, or too new\n");
						}
					}
				}				
				else
				{
					debug("RRD ReadData: poller_output - DS name is '-'\n");
				}
			}
		}
		else
		{
			warn("RRD ReadData: poller_output - Cacti environment is not right [WMRRD12]\n");
		}

		// fudge the missing value, so data is valid. This is horrible.
		// XXX - this should be unnecessary now that NULL is the No-Result
		//if( ($data[IN] == -1) && ($data[OUT] >= 0) ) $data[IN] = 0;
		//if( ($data[OUT] == -1) && ($data[IN] >= 0) ) $data[OUT] = 0;

		debug("RRD ReadData: poller_output - result is ".$data[IN].",".$data[OUT]."\n");
		debug("RRD ReadData: poller_output - ended\n");
	}
	
	function wmrrd_read_from_php_rrd($rrdfile,$cf,$start,$end,$dsnames, &$data ,&$map, &$data_time)
	{
		// not yet implemented - use php-rrdtool to read rrd data. Should be quicker
		if ((1==0) && extension_loaded('RRDTool')) // fetch the values via the RRDtool Extension
		{
			// for the php-rrdtool module, we use an array instead...
			$rrdparams = array("AVERAGE","--start",$start,"--end",$end);
			$rrdreturn = rrd_fetch ($rrdfile,$rrdparams,count($rrdparams));
			print_r($rrdreturn);
			// XXX - figure out what to do with the results here
			$now = $rrdreturn['start'];
			$n=0;
			do {
				$now += $rrdreturn['step'];
				print "$now - ";
				for($i=0;$i<$rrdreturn['ds_cnt'];$i++)
				{
					print $rrdreturn['ds_namv'][$i] . ' = '.$rrdreturn['data'][$n++]." ";
				}
				print "\n";
			} while($now <= $rrdreturn['end']);
		}
	}

	function wmrrd_read_from_real_rrdtool($rrdfile,$cf,$start,$end,$dsnames, &$data, &$map, &$data_time)
	{

		debug("RRD ReadData: traditional style\n");

		// we get the last 800 seconds of data - this might be 1 or 2 lines, depending on when in the
		// cacti polling cycle we get run. This ought to stop the 'some lines are grey' problem that some
		// people were seeing

		// NEW PLAN - READ LINES (LIKE NOW), *THEN* CHECK IF REQUIRED DS NAMES EXIST (AND FAIL IF NOT),
		//     *THEN* GET THE LAST LINE WHERE THOSE TWO DS ARE VALID, *THEN* DO ANY PROCESSING.
		//  - this allows for early failure, and also tolerance of empty data in other parts of an rrd (like smokeping uptime)
		
		$values=array();
		
		# $command = '"'.$map->rrdtool . '" fetch "'.$rrdfile.'" AVERAGE --start '.$start.' --end '.$end;
		$command=$map->rrdtool . " fetch $rrdfile $cf --start $start --end $end";

		debug ("RRD ReadData: Running: $command\n");
		$pipe=popen($command, "r");
		
		$lines=array ();
		$count = 0;
		$linecount = 0;

		if (isset($pipe))
		{
			$headings=fgets($pipe, 4096);
			// this replace fudges 1.2.x output to look like 1.0.x
			// then we can treat them both the same.
			$heads=preg_split("/\s+/", preg_replace("/^\s+/","timestamp ",$headings) );
		
			fgets($pipe, 4096); // skip the blank line
			$buffer='';

			while (!feof($pipe))
			{
				$line=fgets($pipe, 4096);
				debug ("> " . $line);
				$buffer.=$line;
				$lines[]=$line;
				$linecount++;
			}				
			pclose ($pipe);
			
			debug("RRD ReadData: Read $linecount lines from rrdtool\n");
			debug("RRD ReadData: Headings are: $headings\n");

			if( (in_array($dsnames[IN],$heads) || $dsnames[IN] == '-') && (in_array($dsnames[OUT],$heads) || $dsnames[OUT] == '-') )
			{
			    // deal with the data, starting with the last line of output
			     $rlines=array_reverse($lines);
     
			     foreach ($rlines as $line)
				 {
					 debug ("--" . $line . "\n");
					 $cols=preg_split("/\s+/", $line);
					 for ($i=0, $cnt=count($cols)-1; $i < $cnt; $i++) { 
						$h = $heads[$i];
						$v = $cols[$i];
						# print "|$h|,|$v|\n";
						$values[$h] = trim($v); 
					}
	 
					$data_ok=FALSE;
	 
					foreach (array(IN,OUT) as $dir)
					{
						$n = $dsnames[$dir];
						# print "|$n|\n";
						if(array_key_exists($n,$values))
						{
							$candidate = $values[$n];
							if(preg_match('/^\-?\d+\.?\d*e?[+-]?\d*:?$/i', $candidate))
							{
								$data[$dir] = $candidate;
								debug("$candidate is OK value for $n\n");
								$data_ok = TRUE;
							}
						}
					}
					
					if($data_ok)
					{
						// at least one of the named DS had good data
						$data_time = intval($values['timestamp']);	

						// 'fix' a -1 value to 0, so the whole thing is valid
						// (this needs a proper fix!)
						if($data[IN] === NULL) $data[IN] = 0;
						if($data[OUT] === NULL) $data[OUT] = 0;

						// break out of the loop here   
						break;
					}
			     }
			}
			else
			{
			    // report DS name error
			    $names = join(",",$heads);
			    $names = str_replace("timestamp,","",$names);
			    warn("RRD ReadData: At least one of your DS names (".$dsnames[IN]." and ".$dsnames[OUT].") were not found, even though there was a valid data line. Maybe they are wrong? Valid DS names in this file are: $names [WMRRD06]\n");
			}
   
		}
		else
		{
			warn("RRD ReadData: failed to open pipe to RRDTool: ".$php_errormsg." [WMRRD04]\n");
		}
		debug ("RRD ReadDataFromRealRRD: Returning (".($data[IN]===NULL?'NULL':$data[IN]).",".($data[OUT]===NULL?'NULL':$data[IN]).",$data_time)\n");
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
		$SQL[IN] = 'select null';
		$SQL[OUT] = 'select null';
		$rrdfile = $targetstring;

		$multiplier = 8;

		$inbw = NULL;
		$outbw = NULL;
		$data_time = 0;

		if(preg_match("/^(.*\.rrd):([\-a-zA-Z0-9_]+):([\-a-zA-Z0-9_]+)$/",$targetstring,$matches))
		{
			$rrdfile = $matches[1];
			
			$dsnames[IN] = $matches[2];
			$dsnames[OUT] = $matches[3];
						
			debug("Special DS names seen (".$dsnames[IN]." and ".$dsnames[OUT].").\n");
		}

		if(preg_match("/^rrd:(.*)/",$rrdfile,$matches))
		{
			$rrdfile = $matches[1];
		}

		if(preg_match("/^gauge:(.*)/",$rrdfile,$matches))
		{
			$rrdfile = $matches[1];
			$multiplier = 1;
		}

		if(preg_match("/^scale:([+-]?\d*\.?\d*):(.*)/",$rrdfile,$matches)) 
		{
				$rrdfile = $matches[2];
				$multiplier = $matches[1];
		}

		debug("SCALING result by $multiplier\n");
				
		$period = intval($map->get_hint('rrd_period'));
		if($period == 0) $period = 800;
		$start = $map->get_hint('rrd_start');
		if($start == '') {
		    $start = "now-$period";
		    $end = "now";
		}
		else
		{
		    $end = "start+".$period;
		}

		$use_poller_output = intval($map->get_hint('rrd_use_poller_output'));

		if($use_poller_output == 1)
		{
			WeatherMapDataSource_rrd::wmrrd_read_from_poller_output($rrdfile,"AVERAGE",$start,$end, $dsnames, $data,$map, $data_time);
		}
			
		// if poller_output didn't get anything, or if it couldn't/didn't run, do it the old-fashioned way
		// - this will still be the case for the first couple of runs after enabling poller_output support
		//   because there won't be valid data in the weathermap_data table yet.
		if( ($data[IN] === NULL) || ($data[OUT] === NULL) )
		{
			if(file_exists($rrdfile))
			{
				debug ("RRD ReadData: Target DS names are ".$dsnames[IN]." and ".$dsnames[OUT]."\n");
		
				$values=array();
	
				if ((1==0) && extension_loaded('RRDTool')) // fetch the values via the RRDtool Extension
				{
					WeatherMapDataSource_rrd::wmrrd_read_from_php_rrd($rrdfile,"AVERAGE",$start,$end, $dsnames, $data,$map, $data_time);								
				}
				else
				{
					// do this the tried and trusted old-fashioned way
					WeatherMapDataSource_rrd::wmrrd_read_from_real_rrdtool($rrdfile,"AVERAGE",$start,$end, $dsnames, $data,$map, $data_time);
				}
			}
			else
			{
				warn ("Target $rrdfile doesn't exist. Is it a file? [WMRRD06]\n");
			}
		}

		if($data[IN] !== NULL) $data[IN] = $data[IN] * $multiplier;
		if($data[OUT] !== NULL) $data[OUT] = $data[OUT] * $multiplier;
				
		debug ("RRD ReadData: Returning (".($data[IN]===NULL?'NULL':$data[IN]).",".($data[OUT]===NULL?'NULL':$data[IN]).",$data_time)\n");
		
		return( array($data[IN], $data[OUT], $data_time) );
	}
}

// vim:ts=4:sw=4:
?>
