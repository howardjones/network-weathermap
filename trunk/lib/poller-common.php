<?php
// common code used by the poller, the manual-run from the Cacti UI, and from the command-line manual-run.
// this is the easiest way to keep it all consistent!

function weathermap_memory_check($note = "MEM")
{
    global $weathermap_mem_highwater;

    if (true === function_exists("memory_get_usage")) {
        $mem = memory_get_usage();
        if ($mem > $weathermap_mem_highwater) {
            $weathermap_mem_highwater = $mem;
        }
        $mem_used = wmFormatNumberWithMetricPrefix($mem);
        $mem_allowed = ini_get("memory_limit");
        wm_debug("%s: memory_get_usage() says %sBytes used. Limit is %s\n", $note, $mem_used, $mem_allowed);

        return $mem_used;
    }
    return 0;
}

function weathermap_cron_part($value, $checkstring)
{
	// Possible commonents: * (any)
    //       4 (or any digit) - exact match
    //       */5 - every five
    //       5-7  - between 5 and 7 inclusive

	// first, shortcut the most common cases - * and a simple single number
	if ($checkstring == '*') {
        return true;
    }

	if ($checkstring == $value) {
		return (true);
	}
	
	// Cron allows for multiple comma separated clauses, so let's break them
	// up first, and evaluate each one.
	$parts = explode(",", $checkstring);

	foreach ($parts as $part) {
		// just a number
		if ($part === $value) {
			return (true);
		}
		
		// an interval - e.g. */5
		if (1 === preg_match('/\*\/(\d+)/', $part, $matches)) {
			$mod = $matches[1];
		
			if (($value % $mod) === 0) {
				return true;
			}
		}

		// a range - e.g. 4-7
		if (1 === preg_match('/(\d+)\-(\d+)/', $part, $matches)) {
			if (($value >= $matches[1]) && ($value <= $matches[2])) {
				return true;
			}
		}
		
	}
	
	return false;
}

function weathermap_check_cron($time, $string)
{
	if ($string == '') {
        return true;
    }
	if ($string == '*') {
        return true;
    }
	if ($string == '* * * * *') {
        return true;
    }

	$lt = localtime($time, true);
    list($minute, $hour, $weekday, $day, $month) = preg_split('/\s+/', $string);

	if (weathermap_cron_part($lt['tm_min'], $minute)
		&& weathermap_cron_part($lt['tm_hour'], $hour)
		&& weathermap_cron_part($lt['tm_wday'], $weekday)
		&& weathermap_cron_part($lt['tm_mday'], $day)
		&& weathermap_cron_part($lt['tm_mon']+1, $month)
	   ) {
		return true;
	}

    return false;
}

function weathermap_archive_keep($filename, $breaks)
{
    $parts_by_dot = explode(".", $filename);
    array_pop($parts_by_dot);
    $filename_without_ext = join(".", $parts_by_dot);
    $rev_parts_by_dash = array_reverse(explode("-", $filename_without_ext));

    $ctime = mktime(
        $rev_parts_by_dash[1],
        $rev_parts_by_dash[0],
        0,
        $rev_parts_by_dash[3],
        $rev_parts_by_dash[2],
        $rev_parts_by_dash[4]
    );

    $age = time() - $ctime;
    $modulo = null;

    foreach ($breaks as $breakstart => $breakmodulo) {
        if ($age > $breakstart) {
            $modulo = $breakmodulo;
        }
    }

    if (! is_null($modulo)) {
        if (($modulo == 0) || ($ctime % $modulo) != 0) {
            return false;
        }
    }

    return true;
}

/**
 * Handle the expiry of old archived images when archiving is enabled
 *
 * Older than 6 hours, only every 30 minutes is kept  (mod 1800)
 * Older than 24 hours only every hour is kept   (mod 3600)
 * Older than 2 days only every 2 hours is kept  (mod 7200)
 * Older than 5 days only every 6 hours is kept  (mod 21600)
 * Older than 7 days only every 24 hours is kept  (mod 86400)
 * Older than 30 days nothing is kept
 *
 * => leads to (with 5 minute polling):
 *      72 images of last 6 hours
 *      36 images of the next 18 hours
 *      24 images of the next day
 *      36 images of the next 3 days
 *      4 images of the next 2 days
 *      23 images of the next 23 days
 *      = 195 images max (+1 for current image)
 *
 * @param $prefix
 * @param $directory
 */
function weathermap_manage_archiving($prefix, $directory)
{
    $l = strlen($prefix);
    $files = array();

    // What modulo to use after what age, when deciding what to keep
    $breaks = array(
        21600 => 1800,
        86400 => 3600,
        172800 => 7200,
        432000 => 21600,
        604800 => 86400,
        2592000 => 0
    );

    if (is_dir($directory)) {
        if ($dh = opendir($directory)) {
            while (($file = readdir($dh)) !== false) {
                if ( substr($file, 0, $l) == $prefix) {
                    $files[] = $directory.DIRECTORY_SEPARATOR.$file;
                }
            }
            closedir($dh);

            foreach ($files as $file) {
                if (!weathermap_archive_keep($file, $breaks)) {
                        wm_warn("$file will be expired.");
                        unlink($file);
                }
            }
        }
    }
}

function weathermap_directory_writeable($directory_path)
{
    if (! is_dir($directory_path)) {
        wm_warn("Output directory ($directory_path) doesn't exist!. No maps created. You probably need to create that directory, and make it writable by the poller process (like you did with the RRA directory) [WMPOLL07]\n");
        return false;
    }
    
    $testfile = $directory_path.DIRECTORY_SEPARATOR."weathermap.permissions.test";
    
    $testfd = fopen($testfile, 'w');
    if ($testfd) {
        fclose($testfd);
        unlink($testfile);
        return true;
    }
    wm_warn("Output directory ($directory_path) isn't writable (tried to create '$testfile'). No maps created. You probably need to make it writable by the poller process (like you did with the RRA directory) [WMPOLL06]\n");
    return false;
}

function weathermap_get_runlist($map_id = -1, $quietlogging)
{
    global $weathermap_poller_start_time;
    
    $SQL = "select m.*, g.name as groupname from weathermap_maps m,weathermap_groups g where m.group_id=g.id and active='on'";
    if (intval($map_id) >= 0) {
            $SQL .= " and m.id=".intval($map_id)." ";
    }
    $SQL .= "order by sortorder,id";

    $queryrows = db_fetch_assoc($SQL);

    $maplist = array();
    
    // build a list of the maps that we're actually going to run
    if (is_array($queryrows)) {
        foreach ($queryrows as $map) {
            if (weathermap_check_cron( intval($weathermap_poller_start_time), $map['schedule'])) {
                $maplist[] = $map;
            }
        }
    }
    
    if (sizeof($maplist)==0) {
        if ($quietlogging==0) {
            wm_warn("No activated maps found. [WMPOLL05]\n");
        }
    }
    
    return $maplist;
}

function weathermap_run_maps($mydir, $map_id = -1)
{
	# global $config;
	global $weathermap_debugging, $WEATHERMAP_VERSION;
	global $weathermap_map;
	global $weathermap_warncount;
	global $weathermap_poller_start_time;
	global $weathermap_error_suppress;
	
	global $weathermap_mem_highwater;
	
	$weathermap_mem_highwater = 0;
	
	if (true === function_exists('memory_get_usage')) {
		db_execute("replace into settings values('weathermap_initial_memory','"
				. memory_get_usage() . "')");
	}

	// require_once $mydir.DIRECTORY_SEPARATOR."lib".DIRECTORY_SEPARATOR."HTML_ImageMap.class.php";
	require_once $mydir.DIRECTORY_SEPARATOR."lib".DIRECTORY_SEPARATOR."all.php";

	if (true === function_exists('memory_get_usage')) {
		db_execute("replace into settings values('weathermap_loaded_memory','" . memory_get_usage() . "')");
	}
	
	$total_warnings = 0;
	$warning_notes = "";
	
    $start_time = microtime(true);
	
	if ($weathermap_poller_start_time==0) {
		$weathermap_poller_start_time = $start_time;
	}

	$output_directory = $mydir.DIRECTORY_SEPARATOR.'output';
	$confdir = $mydir.DIRECTORY_SEPARATOR.'configs';

	$mapcount = 0;

	// take our debugging cue from the poller - turn on Poller debugging to get weathermap debugging
	if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG) {
		$weathermap_debugging = true;
		$mode_message = "DEBUG mode is on";
		$global_debug = true;
	} else {
		$mode_message = "Normal logging mode. Turn on DEBUG in Cacti for more information";
		$global_debug = false;
	}
	
	$quietlogging = intval(read_config_option("weathermap_quiet_logging"));
	// moved this outside the module_checks, so there should always be something in the logs!
	if ($quietlogging==0) {
        cacti_log("Weathermap $WEATHERMAP_VERSION starting - $mode_message\n", true, "WEATHERMAP");
    }

	if (wm_module_checks()) {
		weathermap_memory_check("MEM Initial");
		// move to the weathermap folder so all those relatives paths don't *have* to be absolute
		$orig_cwd = getcwd();
		chdir($mydir);

		db_execute("replace into settings values('weathermap_last_start_time','".mysql_real_escape_string($start_time)."')");

		// first, see if the output directory exists and is writable
		if (weathermap_directory_writeable($output_directory)) {
            $maplist = weathermap_get_runlist($map_id, $quietlogging);

            wm_debug("Iterating all maps.");

            $imageformat = strtolower(read_config_option("weathermap_output_format"));
            $rrdtool_path =  read_config_option("path_rrdtool");

            foreach ($maplist as $map) {
                // reset the warning counter
                $weathermap_warncount=0;
                // this is what will prefix log entries for this map
                $weathermap_map = "[Map ".$map['id']."] ".$map['configfile'];

                wm_debug("FIRST TOUCH\n");

                $runner = new WeatherMapRunner($confdir, $output_directory, $map['configfile'], $map['filehash'], $imageformat);

                $mapfile = $confdir . DIRECTORY_SEPARATOR . $map['configfile'];
                $htmlfile = $output_directory . DIRECTORY_SEPARATOR . $map['filehash'].".html";
                $imagefile = $output_directory . DIRECTORY_SEPARATOR . $map['filehash'].".".$imageformat;
                $thumbimagefile = $output_directory . DIRECTORY_SEPARATOR . $map['filehash'].".thumb.".$imageformat;
                $thumbimagefile2 = $output_directory . DIRECTORY_SEPARATOR . $map['filehash'].".thumb48.".$imageformat;

                $jsonfile  = $output_directory . DIRECTORY_SEPARATOR . $map['filehash'].".json";

                $statsfile = $output_directory . DIRECTORY_SEPARATOR . $map['filehash'] . '.stats.txt';
                $resultsfile = $output_directory . DIRECTORY_SEPARATOR . $map['filehash'] . '.results.txt';
                // used to write files before moving them into place
                $tempfile = $output_directory . DIRECTORY_SEPARATOR . $map['filehash'] . '.tmp.png';

                $runner->LoadMap();

                if (file_exists($mapfile)) {
                    if ($quietlogging==0) wm_warn("Map: $mapfile -> $htmlfile & $imagefile\n",true);
                    db_execute("replace into settings values('weathermap_last_started_file','".mysql_real_escape_string($weathermap_map)."')");

                    if ($global_debug === false && ($map['debug']=='on' || $map['debug']=='once')) {
                        $weathermap_debugging = true;
                    }
                    else {
                        $weathermap_debugging = $global_debug;
                    }


                    $map_start = microtime(true);


                    weathermap_memory_check("MEM starting $mapcount");
                    $wmap = new Weathermap;
                    $wmap->context = "cacti";

                    // we can grab the rrdtool path from Cacti's config, in this case
                    $wmap->rrdtool  = $rrdtool_path;

                    $wmap->ReadConfig($mapfile);

                    $wmap->add_hint("mapgroup",$map['groupname']);
                    $wmap->add_hint("mapgroupextra",($map['group_id'] ==1 ? "" : $map['groupname'] ));

                    # in the order of precedence - global extras, group extras, and finally map extras
                    $queries = array();
                    $queries[] = "select * from weathermap_settings where mapid=0 and groupid=0";
                    $queries[] = "select * from weathermap_settings where mapid=0 and groupid=".intval($map['group_id']);
                    $queries[] = "select * from weathermap_settings where mapid=".intval($map['id']);

                    foreach ($queries as $sql) {
                        $settingrows = db_fetch_assoc($sql);
                        if (is_array($settingrows) && count($settingrows) > 0) {
                            foreach ($settingrows as $setting) {
                                $set_it = false;
                                if ($setting['mapid']==0 && $setting['groupid']==0) {
                                    wm_debug("Setting additional (all maps) option: ".$setting['optname']." to '".$setting['optvalue']."'\n");
                                    $set_it = true;
                                } elseif ($setting['groupid']!=0) {
                                    wm_debug("Setting additional (all maps in group) option: ".$setting['optname']." to '".$setting['optvalue']."'\n");
                                    $set_it = true;
                                } else {
                                    wm_debug("Setting additional map-global option: ".$setting['optname']." to '".$setting['optvalue']."'\n");
                                    $set_it = true;
                                }
                                if ($set_it) {
                                    $wmap->add_hint($setting['optname'],$setting['optvalue']);

                                    if (substr($setting['optname'],0,7)=='nowarn_') {
                                        $code = strtoupper(substr($setting['optname'],7) );
                                        $weathermap_error_suppress[] = $code;
                                    }
                                }
                            }
                        }
                    }


                    weathermap_memory_check("MEM postread $mapcount");
                    $wmap->readData();
                    weathermap_memory_check("MEM postdata $mapcount");

                    $configured_imageuri = $wmap->imageuri;
                    $wmap->imageuri = 'weathermap-cacti-plugin.php?action=viewimage&id='.$map['filehash']."&time=".time();

                    if ($quietlogging==0) {
                        wm_warn("About to write image file. If this is the last message in your log, increase memory_limit in php.ini [WMPOLL01]\n",true);
                    }
                    weathermap_memory_check("MEM pre-render $mapcount");

                    // Write the image to a temporary file first - it turns out that libpng is not that fast
                    // and this way we avoid showing half a map
                    $wmap->drawMapImage($tempfile,$thumbimagefile,read_config_option("weathermap_thumbsize"));

                    // Firstly, don't move or delete anything if the image saving failed
                    if (file_exists($tempfile)){
                        // Don't try and delete a non-existent file (first run)
                        if (file_exists($imagefile)) {
                            unlink($imagefile);
                        }
                        rename($tempfile,$imagefile);
                    }

                    $im = imagecreatefrompng($thumbimagefile);
                    $im48 = imagecreatetruecolor(48,48);
                    imagecopyresampled($im48, $im, 0, 0, 0, 0, 48, 48, imagesx($im), imagesy($im));
                    imagepng($im48,$thumbimagefile2);
                    imagedestroy($im48);
                    imagedestroy($im);

                    if ($quietlogging==0) {
                        wm_warn("Wrote map to $imagefile and $thumbimagefile\n",true);
                    }
                    $fd = @fopen($htmlfile, 'w');
                    if ($fd != false) {
                        fwrite($fd, $wmap->makeHTML('weathermap_'.$map['filehash'].'_imap'));
                        fclose($fd);
                        wm_debug("Wrote HTML to $htmlfile");
                    } else {
                        if (file_exists($htmlfile)) {
                            wm_warn("Failed to overwrite $htmlfile - permissions of existing file are wrong? [WMPOLL02]\n");
                        } else {
                            wm_warn("Failed to create $htmlfile - permissions of output directory are wrong? [WMPOLL03]\n");
                        }
                    }

                    $wmap->dumpUsageStatistics($statsfile);
                    $wmap->writeDataFile($resultsfile);
                    // $wmap->WriteJSONFile($jsonfile);

                    // put back the configured imageuri
                    $wmap->imageuri = $configured_imageuri;

                    // if an htmloutputfile was configured, output the HTML there too
                    // but using the configured imageuri and imagefilename
                    if ($wmap->htmloutputfile != "") {
                        $htmlfile = $wmap->htmloutputfile;
                        $fd = @fopen($htmlfile, 'w');

                        if ($fd !== false) {
                            fwrite($fd,
                                $wmap->makeHTML('weathermap_' . $map['filehash'] . '_imap'));
                            fclose($fd);
                            wm_debug("Wrote HTML to %s\n", $htmlfile);
                        } else {
                            if (true === file_exists($htmlfile)) {
                                wm_warn('Failed to overwrite ' . $htmlfile
                                    . " - permissions of existing file are wrong? [WMPOLL02]\n");
                            } else {
                                wm_warn('Failed to create ' . $htmlfile
                                    . " - permissions of output directory are wrong? [WMPOLL03]\n");
                            }
                        }
                    }

                    if ($wmap->imageoutputfile != "" && $wmap->imageoutputfile != "weathermap.png" && file_exists($imagefile)) {
                        // copy the existing file to the configured location too
                        @copy($imagefile, $wmap->imageoutputfile);
                    }

                    // If archiving is enabled for this map, then save copies with datestamps, for animation etc
                    if ($map['archiving'] == 'on') {
                        // TODO - additionally save a copy with a datestamp file format
                        $datestamp = strftime("%Y-%m-%d-%H-%M",$weathermap_poller_start_time);
                        $archive_filename = $output_directory . DIRECTORY_SEPARATOR . sprintf("%s-archive-%s.%s",$map['filehash'], $datestamp, $imageformat);
                        @copy($imagefile, $archive_filename);

                        weathermap_manage_archiving($map['filehash'] . "-archive-", $output_directory);
                    }

                    // if the user explicitly defined a data file, write it there too
                    if ($wmap->dataoutputfile) {
                        $map->WriteDataFile($map->dataoutputfile);
                    }

                    $processed_title = $wmap->processString($wmap->title,$wmap);

                    db_execute("update weathermap_maps set titlecache='".mysql_real_escape_string($processed_title)."' where id=".intval($map['id']));
                    if (intval($wmap->thumb_width) > 0) {
                        db_execute("update weathermap_maps set thumb_width=".intval($wmap->thumb_width).", thumb_height=".intval($wmap->thumb_height)." where id=".intval($map['id']));
                    }

                    $runner->CleanUp();
                    unset($runner);

                    $wmap->cleanUp();
                    unset($wmap);

                    $map_end = microtime(true);

                    $map_duration = $map_end - $map_start;
                    wm_debug("TIME: $mapfile took %f seconds.\n", $map_duration);
                    weathermap_memory_check("MEM after $mapcount");
                    $mapcount++;
                    db_execute("replace into settings values('weathermap_last_finished_file','".mysql_real_escape_string($weathermap_map)."')");
                } else {
                    wm_warn("Mapfile $mapfile is not readable or doesn't exist [WMPOLL04]\n");
                }

                // if the debug mode was set to once for this map, then that
                // time has now passed, and it can be turned off again.
                $newdebug = $map['debug'];
                if ($newdebug == 'once') {
                    $newdebug = 'off';
                }

                db_execute(sprintf(
                    "update weathermap_maps set warncount=%d, runtime=%f,debug='%s',lastrun=NOW() where id=%d",
                    $weathermap_warncount,
                    $map_duration,
                    $newdebug,
                    $map['id'])
                );

                $total_warnings += $weathermap_warncount;
                $weathermap_warncount = 0;
                $weathermap_map="";
            }
            wm_debug("Iterated all $mapcount maps.\n");
		} else {
			wm_warn("Output directory ($output_directory) doesn't exist!. No maps created. You probably need to create that directory, and make it writable by the poller process (like you did with the RRA directory) [WMPOLL07]\n");
			$total_warnings++;
			$warning_notes .= " (Output directory problem prevents any maps running WMPOLL07)";
		}
                
		weathermap_memory_check("MEM Final");
		chdir($orig_cwd);
		
        $end_time = microtime(true);
		$duration = $end_time - $start_time;
		
		$stats_string = sprintf('%s: %d maps were run in %.2f seconds with %d warnings', date(DATE_RFC822), $mapcount, $duration, $total_warnings);
		
		if (true === function_exists("memory_get_peak_usage")) {
			$peak_memory = memory_get_peak_usage();
			db_execute("replace into settings values('weathermap_peak_memory','"
					. $peak_memory . "')");
			$stats_string .= sprintf(" using %sbytes peak memory", wmFormatNumberWithMetricPrefix($peak_memory));
		}

		if ($quietlogging==0) {
            wm_warn("STATS: Weathermap $WEATHERMAP_VERSION run complete - $stats_string\n", true);
        }
		db_execute("replace into settings values('weathermap_last_stats','".mysql_real_escape_string($stats_string)."')");
		db_execute("replace into settings values('weathermap_last_finish_time','".mysql_real_escape_string($end_time)."')");
		db_execute("replace into settings values('weathermap_last_map_count','". mysql_real_escape_string($mapcount) . "')");

		if (true === function_exists('memory_get_usage')) {
			db_execute("replace into settings values('weathermap_final_memory','" . memory_get_usage() . "')");
            db_execute("replace into settings values('weathermap_highwater_memory','" . $weathermap_mem_highwater . "')");
		}
		if (true === function_exists("memory_get_peak_usage")) {
			db_execute("replace into settings values('weathermap_peak_memory','" . memory_get_peak_usage() . "')");
		}
	} else {
		wm_warn("Required modules for PHP Weathermap $WEATHERMAP_VERSION were not present. Not running. [WMPOLL08]\n");
	}
}

// vim:ts=4:sw=4:
