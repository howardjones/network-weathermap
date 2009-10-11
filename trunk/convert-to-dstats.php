<?php

	$mapfile = "configs/097-test.conf";

	#
	# Change the uncommented line to point to your Cacti installation
	#
	$cacti_base = "C:/xampp/htdocs/cacti/";
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
		die("Couldn't find a usable Cacti config");
	}

	require_once 'Weathermap.class.php';
		

	$map = new WeatherMap;

	//   $map->debugging = TRUE;
 	$weathermap_debugging=TRUE;

	$map->ReadConfig($mapfile);

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
					$tindex = 0;
					foreach ($myobj->targets as $target)
					{
						debug ("ReadData: New Target: $target[4]\n");

						$targetstring = $target[0];
						$multiply = $target[1];
						$multiplier = 8;
						$dsnames[IN] = "traffic_in";
						$dsnames[OUT] = "traffic_out";
																										
						if($target[5] == "WeatherMapDataSource_dsstats")
						{
							# list($in,$out,$datatime) =  $map->plugins['data'][ $target[5] ]->ReadData($targetstring, $map, $myobj);
							debug("ConvertDS: $targetstring is a candidate for conversion.");
							$rrdfile = "";
							
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
							
							$path_rra = $config["rra_path"];
							$db_rrdname = $rrdfile;
							$db_rrdname = str_replace($path_rra,"<path_rra>",$db_rrdname);
		
							debug("ConvertDS: Looking for $db_rrdname in the database.");
							
							$SQLcheck = "select data_template_data.local_data_id from data_template_data,data_template_rrd where data_template_data.local_data_id=data_template_rrd.local_data_id and data_template_data.data_source_path='".mysql_real_escape_string($db_rrdname)."'";
							debug($SQLcheck);
							$results = db_fetch_assoc($SQLcheck);
							
							if(isset($results['local_data_id']))
							{
								$new_target = sprintf("dsstats:%d:%s:%s", $results['local_data_id'], $dsnames[IN], $dsnames[OUT]);
								debug("Converting to $new_target");		
							}
							else
							{
								warn("Failed to find a match for $db_rrdname - can't convert to DSStats.");
							}
						}
					
						$tindex++;
					}

					debug ("ReadData complete for $type $name: $total_in $total_out\n");
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
	
	$map->WriteConfig("output.conf");

	print "Wrote new config\n";

	// vim:ts=4:sw=4:
?>