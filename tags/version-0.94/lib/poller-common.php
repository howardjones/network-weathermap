<?php
// common code used by the poller, the manual-run from the Cacti UI, and from the command-line manual-run.
// this is the easiest way to keep it all consistent!

function weathermap_memory_check($note="MEM")
{
	if(function_exists("memory_get_usage"))
	{
		$mem_used = nice_bandwidth(memory_get_usage());
		$mem_allowed = ini_get("memory_limit");
		debug("$note: memory_get_usage() says ".$mem_used."Bytes used. Limit is ".$mem_allowed."\n");
	}
}

function weathermap_run_maps($mydir) {
	global $config;
	global $weathermap_debugging, $WEATHERMAP_VERSION;

	include_once($mydir.DIRECTORY_SEPARATOR."HTML_ImageMap.class.php");
	include_once($mydir.DIRECTORY_SEPARATOR."Weathermap.class.php");

	$start_time = time();

	$outdir = $mydir.DIRECTORY_SEPARATOR.'output';
	$confdir = $mydir.DIRECTORY_SEPARATOR.'configs';

	$mapcount = 0;

	// take our debugging cue from the poller - turn on Poller debugging to get weathermap debugging
	if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG)
	{
		$weathermap_debugging = TRUE;
		$mode_message = "DEBUG mode is on";
	}
	else
	{
		$mode_message = "Normal logging mode. Turn on DEBUG in Cacti for more information";
	}
	$quietlogging = read_config_option("weathermap_quiet_logging");  
	// moved this outside the module_checks, so there should always be something in the logs!
	if($quietlogging==0) cacti_log("Weathermap $WEATHERMAP_VERSION starting - $mode_message",true,"WEATHERMAP");

	if(module_checks())
	{
		weathermap_memory_check("MEM Initial");
		// move to the weathermap folder so all those relatives paths don't *have* to be absolute
		$orig_cwd = getcwd();
		chdir($mydir);

		// first, see if the output directory even exists
		if(is_dir($outdir))
		{
			// next, make sure that we stand a chance of writing files
			//// $testfile = realpath($outdir."weathermap.permissions.test");
			$testfile = $outdir.DIRECTORY_SEPARATOR."weathermap.permissions.test";
			$testfd = fopen($testfile, 'w');
			if($testfd)
			{ 
				fclose($testfd); 
				unlink($testfile);

				$queryrows = db_fetch_assoc("select * from weathermap_maps where active='on' order by sortorder,id");

				if( is_array($queryrows) )
				{
					debug("Iterating all maps.");

					$imageformat = strtolower(read_config_option("weathermap_output_format"));

					foreach ($queryrows as $map) {
						$mapfile = $confdir.DIRECTORY_SEPARATOR.$map['configfile'];
						$htmlfile = $outdir.DIRECTORY_SEPARATOR."weathermap_".$map['id'].".html";
						$imagefile = $outdir.DIRECTORY_SEPARATOR."weathermap_".$map['id'].".".$imageformat;
						$thumbimagefile = $outdir.DIRECTORY_SEPARATOR."weathermap_thumb_".$map['id'].".".$imageformat;

						if(file_exists($mapfile))
						{
							if($quietlogging==0) warn("Map: $mapfile -> $htmlfile & $imagefile");
							weathermap_memory_check("MEM starting $mapcount");
							$wmap = new Weathermap;
							$wmap->context = "cacti";

							// we can grab the rrdtool path from Cacti's config, in this case
							$wmap->rrdtool  = read_config_option("path_rrdtool");

							$wmap->ReadConfig($mapfile);
							weathermap_memory_check("MEM postread $mapcount");
							$wmap->ReadData();
							weathermap_memory_check("MEM postdata $mapcount");

							// $wmap->imageuri = $config['url_path'].'/plugins/weathermap/output/weathermap_'.$map['id'].".".$imageformat;
							$wmap->imageuri = 'output/weathermap_'.$map['id'].".".$imageformat;

							if($quietlogging==0) warn("About to write image file. If this is the last message in your log, increase memory_limit in php.ini [WMPOLL01]");
							weathermap_memory_check("MEM pre-render $mapcount");
							
							$wmap->DrawMap($imagefile,$thumbimagefile,read_config_option("weathermap_thumbsize"));
							
							if($quietlogging==0) warn("Wrote map to $imagefile and $thumbimagefile");
							$fd = @fopen($htmlfile, 'w');
							if($fd != FALSE)
							{
								fwrite($fd, $wmap->MakeHTML('weathermap_'.$map['id'].'_imap'));
								fclose($fd);
								debug("Wrote HTML to $htmlfile");
							}
							else
							{
								if(file_exists($htmlfile))
								{
									warn("Failed to overwrite $htmlfile - permissions of existing file are wrong? [WMPOLL02]\n");
								}
								else
								{
									warn("Failed to create $htmlfile - permissions of output directory are wrong? [WMPOLL03]\n");
								}
							}

							db_execute("update weathermap_maps set titlecache='".mysql_real_escape_string($wmap->title)."' where id=".$map['id']);
							
							unset($wmap);
							weathermap_memory_check("MEM after $mapcount");
							$mapcount++;
						}
						else
						{
							warn("Mapfile $mapfile is not readable or doesn't exist [WMPOLL04]");
						}
					}
					debug("Iterated all $mapcount maps.");
				}
				else
				{
					if($quietlogging==0) warn("No activated maps found. [WMPOLL05]");
				}
			}
			else
			{
				warn("Output directory ($outdir) isn't writable (tried to create '$testfile'). No maps created. You probably need to make it writable by the poller process (like you did with the RRA directory) [WMPOLL06]");
			}
		}
		else
		{
			warn("Output directory ($outdir) doesn't exist!. No maps created. You probably need to create that directory, and make it writable by the poller process (like you did with the RRA directory) [WMPOLL07]");
		}
		weathermap_memory_check("MEM Final");
		chdir($orig_cwd);
		$duration = time() - $start_time;
		if($quietlogging==0) warn("Weathermap $WEATHERMAP_VERSION run complete - $mapcount maps were run in $duration seconds");
	}
	else
	{
		warn("Required modules for PHP Weathermap $WEATHERMAP_VERSION were not present. Not running. [WMPOLL08]");
	}
}


// vim:ts=4:sw=4:
?>
