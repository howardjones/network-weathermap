<?php

	include_once 'editor-config.php';
	require_once 'Weathermap.class.php';
	
	// check if the goalposts have moved
	if( is_dir($cacti_base) && file_exists($cacti_base."/include/global.php") )
	{
		// include the cacti-config, so we know about the database
		include_once($cacti_base."/include/global.php");
		$config['base_url'] = $cacti_url;
		$cacti_found = TRUE;
	}
	elseif( is_dir($cacti_base) && file_exists($cacti_base."/include/config.php") )
	{
		// include the cacti-config, so we know about the database
		include_once($cacti_base."/include/config.php");
	
		$config['base_url'] = $cacti_url;
		$cacti_found = TRUE;
	}
	else
	{
		$cacti_found = FALSE;
		
		die("Couldn't load Cacti config, so we can't access the database. Check your editor-config.php\n");
	}
		
	$mapfile = "configs/097-test.conf";

	$map = new WeatherMap;

	//   $map->debugging = TRUE;
 	$weathermap_debugging=TRUE;

	$map->ReadConfig($mapfile);
	
	debug("Running Init() for Data Source Plugins...\n");
	foreach ($map->datasourceclasses as $ds_class)
	{
		// make an instance of the class
		$dsplugins[$ds_class] = new $ds_class;
		debug("Running $ds_class"."->Init()\n");
		# $ret = call_user_func(array($ds_class, 'Init'), $this);
		assert('isset($map->plugins["data"][$ds_class])');

		$ret = $map->plugins['data'][$ds_class]->Init($map);

		if(! $ret)
		{
			debug("Removing $ds_class from Data Source list, since Init() failed\n");
			$map->activedatasourceclasses[$ds_class]=0;
			# unset($this->datasourceclasses[$ds_class]);
		}
	}
	debug("Finished Initialising Plugins...\n");
	
	
	
		
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
			$name = $myobj->name;
			
			debug ("ConvertDS for $type $name: \n");
			
			foreach ($myobj->targets as $target)
			{
				debug ("ConvertDS: New Target: $target[4]\n");
				
			if ($target[4] != '')
			{
				// processstring won't use notes (only hints) for this string
				
				$dsnames[IN] = "traffic_in";
				$dsnames[OUT] = "traffic_out";
				
				$targetstring = $map->ProcessString($target[4], $myobj, FALSE, FALSE);
				if($target[4] != $targetstring) debug("Targetstring is now $targetstring\n");

				// if the targetstring starts with a -, then we're taking this value OFF the aggregate
				$multiply = 1;
				if(preg_match("/^-(.*)/",$targetstring,$matches))
				{
					$targetstring = $matches[1];
					$multiply = -1 * $multiply;
				}
				
				// if the remaining targetstring starts with a number and a *-, then this is a scale factor
				if(preg_match("/^(\d+\.?\d*)\*(.*)/",$targetstring,$matches))
				{
					$targetstring = $matches[2];
					$multiply = $multiply * floatval($matches[1]);
				}

				if($multiply != 1)
				{
					debug("Will multiply result by $multiply\n");
				}
				$recognised = $map->plugins['data']['WeatherMapDataSource_rrd']->Recognise($targetstring);
				
				if($recognised)
				{
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
					
					
					exit();
				}
				else
				{
					debug("ConvertDS: $targetstring is a not candidate for conversion.");
				}
			}
							
			}
		}
	}

$map->WriteConfig("output.conf");

print "Wrote new config\n";

// vim:ts=4:sw=4:
?>
