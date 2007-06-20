<?php
// RRDtool datasource plugin.
//     gauge:filename.rrd:ds_in:ds_out
//     filename.rrd:ds_in:ds_out
//     filename.rrd:ds_in:ds_out
//
class WeatherMapDataSource_rrd extends WeatherMapDataSource {

	function Init(&$map)
	{
		if (extension_loaded('RRDTool')) // fetch the values via the RRDtool Extension
		{
			debug("RRD DS: Using RRDTool php extension.\n");
			return(TRUE);
		}
		else
		{
			if (file_exists($map->rrdtool)) {
				if((function_exists('is_executable')) && (!is_executable($map->rrdtool)))
				{
					warn("RRD DS: RRDTool exists but is not executable?\n");
					return(FALSE);
				}
				$map->rrdtool_check="FOUND";
				return(TRUE); 
			}
			// normally, DS plugins shouldn't really pollute the logs
			// this particular one is important to most users though...
			if($map->context=='cli')
			{
				warn("RRD DS: Can't find RRDTOOL. Check line 29 of the 'weathermap' script.\nRRD-based TARGETs will fail.\n");
			}
			if($map->context=='cacti')
			{    // unlikely to ever occur
				warn("RRD DS: Can't find RRDTOOL. Check your Cacti config.\n");
			}
		}

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

	// Actually read data from a data source, and return it
	// returns a 3-part array (invalue, outvalue and datavalid time_t)
	// invalue and outvalue should be -1,-1 if there is no valid data
	// data_time is intended to allow more informed graphing in the future
	function ReadData($targetstring, &$map, &$item)
	{
		$in_ds = "traffic_in";
		$out_ds = "traffic_out";
		$rrdfile = $targetstring;

		$multiplier = 8;

		$inbw=-1;
		$outbw=-1;
		$data_time = 0;

		if(preg_match("/^(.*\.rrd):([\-a-zA-Z0-9_]+):([\-a-zA-Z0-9_]+)$/",$targetstring,$matches))
		{
			$in_ds = $matches[2];
			$out_ds = $matches[3];
			$rrdfile = $matches[1];
			debug("Special DS names seen ($in_ds and $out_ds).\n");
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


		// we get the last 800 seconds of data - this might be 1 or 2 lines, depending on when in the
		// cacti polling cycle we get run. This ought to stop the 'some lines are grey' problem that some
		// people were seeing

		if(file_exists($rrdfile))
		{
			if(1==1)
			{
				debug ("RRD ReadData: Target DS names are $in_ds and $out_ds\n");

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

				if (extension_loaded('RRDTool')) // fetch the values via the RRDtool Extension
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
				else
				{

			#		$command = '"'.$map->rrdtool . '" fetch "'.$rrdfile.'" AVERAGE --start '.$start.' --end '.$end;
					$command=$map->rrdtool . " fetch $rrdfile AVERAGE --start $start --end $end";
		
	
					debug ("RRD ReadData: Running: $command\n");
					$pipe=popen($command, "r");
					
					$lines=array ();
					$count = 0;
					$linecount = 0;
	
					if (isset($pipe))
					{
						$headings=fgets($pipe, 4096);
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
	
						$rlines=array_reverse($lines);
						$gotline=0;
						$theline='';
	
						foreach ($rlines as $line)
						{
							debug ("--" . $line . "\n");
							$cols=preg_split("/\s+/", $line);
							$dataok=1;
							$ii=0;
	
							foreach ($cols as $col)
							{
								# if( ! is_numeric($col) ) { $dataok=0; }
								if (trim($col) != '' && !preg_match('/^\d+\.?\d*e?[+-]?\d*:?$/i', $col))
								{
									$dataok=0;
									debug ("RRD ReadData: $ii: This isn't a number: [$col]\n");
								}
	
								# if($col=='nan') { $dataok=0; }
								$ii++;
							}
	
							if ($gotline == 0 && $dataok == 1 && trim($line) != '')
							{
								debug ("RRD ReadData: Found a good line: $line ($headings)\n");
								$theline=$line;
								$gotline=1;
								$countwas=$count;
							}
	
							$count++;
						}
					}
					else
					{
						warn("RRD ReadData: failed to open pipe to RRDTool: ".$php_errormsg."\n");
					}
				}

				if ($theline != '')
				{
					if ($countwas > 2) { warn
						("RRD ReadData: Data is not most recent entry ($countwas) for link: $targetstring\n"); }

					debug ("RRD ReadData: Our line is $theline\n");
					$cols=preg_split("/\s+/", $theline);
					// this replace fudges 1.2.x output to look like 1.0.x
					// then we can treat them both the same.
					$heads=preg_split("/\s+/", preg_replace("/^\s+/","timestamp ",$headings) );

					# $values = array_combine($heads,$cols);
					for ($i=0, $cnt=count($cols); $i < $cnt; $i++) { $values[$heads[$i]] = $cols[$i]; }

					// as long as no-one actually manages to create an RRD with a DS of '-', then this will just fall through to 0 for '-'
					if( isset($values[$in_ds]) || isset($values[$out_ds]) )
					{
						$inbw=0; $outbw=0;
						if(isset($values[$in_ds]) ) $inbw=$values[$in_ds] * $multiplier;
						if(isset($values[$out_ds]) ) $outbw=$values[$out_ds] * $multiplier;
						$data_time = $values['timestamp'];
						$data_time = preg_replace("/:/","",$data_time);
					}
					else
					{
						warn("RRD ReadData: Neither of your DS names ($in_ds & $out_ds) were found, even though there was a valid data line. Maybe they are wrong?");
					}
				}
			}
		}
		else
		{
			warn ("Target $rrdfile doesn't exist. Is it a file?\n");
		}

		debug ("RRD ReadData: Returning ($inbw,$outbw,$data_time)\n");

		return( array($inbw, $outbw, $data_time) );
	}
}

// vim:ts=4:sw=4:
?>
