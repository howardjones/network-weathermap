<?php

	#
	# Change the uncommented line to point to your Cacti installation
	#
	$cacti_base = dirname(__FILE__)."/../../";
	# $cacti_base = "C:/xampp/htdocs/cacti/";
	# $cacti_base = "/var/www/html/cacti/";
	# $cacti_base = "/Applications/XAMPP/htdocs/cacti/";
		
	// check if the goalposts have moved
	if( is_dir($cacti_base) && file_exists($cacti_base."/include/global.php") )
	{
	        // include the cacti-config, so we know about the database
	        require_once($cacti_base."/include/global.php");
	}
	elseif( is_dir($cacti_base) && file_exists($cacti_base."/include/config.php") )
	{
	        // include the cacti-config, so we know about the database
	        require_once($cacti_base."/include/config.php");
	}
	else
	{
		die("Couldn't find a usable Cacti config - check the first few lines of ".__FILE__."\n");
	}

	require_once 'Weathermap.class.php';
	require_once 'Console/Getopt.php';

	$reverse = 0;
	$inputfile = "";
	$outputfile = "";
	$converted = 0;
	$candidates = 0;
	$totaltargets = 0;
		
	$cg=new Console_Getopt();
	$short_opts='';
	$long_opts=array
			(
					"help",
					"input=",
					"output=",
					"debug",
					"reverse",
			);

	$args=$cg->readPHPArgv();
	$ret=$cg->getopt($args, $short_opts, $long_opts);

	if (PEAR::isError($ret)) { die ("Error in command line: " . $ret->getMessage() . "\n (try --help)\n"); }
		
	$gopts=$ret[0];

	if (sizeof($gopts) > 0)
	{
        foreach ($gopts as $o)
        {
			switch ($o[0])
            {
				case '--debug':
                    $weathermap_debugging=TRUE;
					break;
                case '--input':
					$inputfile=$o[1];
					break;
				case '--output':
					$outputfile=$o[1];
					break;
				case '--reverse':
					$reverse = 1;
					break;
				case 'help':
				default:
					print "Weathermap DSStats converter. Converts rrd targets to DSStats\n";
					print "-------------------------------------------------------------\n";
					print "Usage: php convert-to-dstats.php [options]\n\n";
					print " --input {filename}         - File to read from\n";
					print " --output {filename}        - File to write to\n";
				#	print " --reverse                  - Convert from DSStats to RRDtool instead\n";
					print " --debug                    - Enable debugging output\n";
					print " --help                    - Show this message\n";
					exit();
			}
		}
	}

	if($inputfile == "" || $outputfile == "")
	{
		print "You must specify an input and output file. See --help.\n";
		exit();
	}
	
	$map = new WeatherMap;
	
	$map->context = 'cacti';
	$map->rrdtool  = read_config_option("path_rrdtool");
	
	print "Reading config from $inputfile\n";
	
	$map->ReadConfig($inputfile);

	$map->DatasourceInit();
	$map->ProcessTargets();
	
	$allitems = array(&$map->links, &$map->nodes);
	reset($allitems);
	
	while( list($kk,) = each($allitems))
	{
		unset($objects);
		$objects = &$allitems[$kk];

		reset($objects);
		while (list($k,) = each($objects))
		{
			unset($myobj);
			$myobj = &$objects[$k];

			$type = $myobj->my_type();

			$name=$myobj->name;
			debug ("ReadData for $type $name: \n");

			if( ($type=='LINK' && isset($myobj->a)) || ($type=='NODE' && !is_null($myobj->x) ) )
			{
				if (count($myobj->targets)>0)
				{
					$totaltargets++;
					$tindex = 0;
					foreach ($myobj->targets as $target)
					{
						debug ("ReadData: New Target: $target[4]\n");

						$targetstring = $target[0];
						$multiply = $target[1];					
																										
						if($reverse == 0 && $target[5] == "WeatherMapDataSource_rrd")
						{
							$candidates++;
							# list($in,$out,$datatime) =  $map->plugins['data'][ $target[5] ]->ReadData($targetstring, $map, $myobj);
							debug("ConvertDS: $targetstring is a candidate for conversion.");
							$rrdfile = $targetstring;
							$multiplier = 8;
							$dsnames[IN] = "traffic_in";
							$dsnames[OUT] = "traffic_out";
							
							if(preg_match("/^(.*\.rrd):([\-a-zA-Z0-9_]+):([\-a-zA-Z0-9_]+)$/",$targetstring,$matches))
							{
								$rrdfile = $matches[1];
								
								$dsnames[IN] = $matches[2];
								$dsnames[OUT] = $matches[3];
											
								debug("ConvertDS: Special DS names seen (".$dsnames[IN]." and ".$dsnames[OUT].").\n");
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
							
							$path_rra = $config["rra_path"];
							$db_rrdname = $rrdfile;
							$db_rrdname = str_replace($path_rra,"<path_rra>",$db_rrdname);
							# special case for relative paths
							$db_rrdname = str_replace("../../rra","<path_rra>",$db_rrdname);
							
							if($db_rrdname != $rrdfile)
							{		
								debug("ConvertDS: Looking for $db_rrdname in the database.");
								
								$SQLcheck = "select data_template_data.local_data_id from data_template_data,data_template_rrd where data_template_data.local_data_id=data_template_rrd.local_data_id and data_template_data.data_source_path='".mysql_real_escape_string($db_rrdname)."'";
								debug("ConvertDS: ".$SQLcheck);
								$results = db_fetch_assoc($SQLcheck);
								
								if( (sizeof($results) > 0) && (isset($results[0]['local_data_id']) ) )
								{							
									$new_target = sprintf("dsstats:%d:%s:%s", $results[0]['local_data_id'], $dsnames[IN], $dsnames[OUT]);
									$m = $multiply * $multiplier;
									if( $m != 1)
									{
										if($m == -1) $new_target = "-".$new_target;
										if($m == intval($m))
										{
											$new_target = sprintf("%d*%s",$m,$new_target);
										}
										else
										{
											$new_target = sprintf("%f*%s",$m,$new_target);
										}
										
									}
									
									debug("ConvertDS: Converting to $new_target");		
									$converted++;

									if($type == 'NODE')
									{
										$map->nodes[$name]->targets[$tindex][4] = $new_target;
									}
									if($type == 'LINK')
									{
										$map->links[$name]->targets[$tindex][4] = $new_target;
									}											
									
								}
								else
								{
									warn("ConvertDS: Failed to find a match for $db_rrdname - can't convert to DSStats.");
								}
							}
							else
							{
								warn("ConvertDS: $rrdfile doesn't match with $path_rra - not bothering to look in the database.");
							}
						}
						
						// XXX - not implemented yet!
						if($reverse == 1 && $target[5] == "WeatherMapDataSource_dsstats" && 1==0)
						{							
							$candidates++;
							# list($in,$out,$datatime) =  $map->plugins['data'][ $target[5] ]->ReadData($targetstring, $map, $myobj);
							debug("ConvertDS: $targetstring is a candidate for conversion.");
							
							$multiplier = 1;
							$dsnames[IN] = "traffic_in";
							$dsnames[OUT] = "traffic_out";
														
							$path_rra = $config["rra_path"];
							$db_rrdname = $rrdfile;
							$db_rrdname = str_replace($path_rra,"<path_rra>",$db_rrdname);
							# special case for relative paths
							$db_rrdname = str_replace("../../rra","<path_rra>",$db_rrdname);
							
									
							debug("ConvertDS: Looking for $db_rrdname in the database.");
							
							$SQLcheck = "select data_template_data.local_data_id from data_template_data,data_template_rrd where data_template_data.local_data_id=data_template_rrd.local_data_id and data_template_data.data_source_path='".mysql_real_escape_string($db_rrdname)."'";
							debug("ConvertDS: ".$SQLcheck);
							$results = db_fetch_assoc($SQLcheck);
							
							if( (sizeof($results) > 0) && (isset($results[0]['local_data_id']) ) )
							{							
								$new_target = sprintf("dsstats:%d:%s:%s", $results[0]['local_data_id'], $dsnames[IN], $dsnames[OUT]);
								$m = $multiply * $multiplier;
								if( $m != 1)
								{
									if($m == -1) $new_target = "-".$new_target;
									if($m == intval($m))
									{
										$new_target = sprintf("%d*%s",$m,$new_target);
									}
									else
									{
										$new_target = sprintf("%f*%s",$m,$new_target);
									}
									
								}
								
								debug("ConvertDS: Converting to $new_target");		
								$converted++;

								if($type == 'NODE')
								{
									$map->nodes[$name]->targets[$tindex][4] = $new_target;
								}
								if($type == 'LINK')
								{
									$map->links[$name]->targets[$tindex][4] = $new_target;
								}											
								
							}
							else
							{
								warn("ConvertDS: Failed to find a match for $db_rrdname - can't convert back to rrdfile.");
							}
							
						}
					
						$tindex++;
					}

					debug ("ReadData complete for $type $name\n");
				}
				else
				{
					debug("ReadData: No targets for $type $name\n");
				}
			}
			else
			{
				debug("ReadData: Skipping $type $name that looks like a template\n.");
			}
			
			unset($myobj);
		}
	}
	
	$map->WriteConfig($outputfile);

	print "Wrote new config to $outputfile\n";
	
	print "$totaltargets targets, $candidates rrd-based targets, $converted were actually converted.\n";

	// vim:ts=4:sw=4:
?>
