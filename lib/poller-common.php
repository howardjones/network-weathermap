<?php
// common code used by the poller, the manual-run from the Cacti UI, and from the command-line manual-run.
// this is the easiest way to keep it all consistent!

function weathermap_memory_check($note = 'MEM')
{
    global $weathermap_mem_highwater;

    if (true === function_exists('memory_get_usage')) {
        $mem = memory_get_usage();

        if ($mem > $weathermap_mem_highwater) {
            $weathermap_mem_highwater = $mem;
        }
        $mem_used = nice_bandwidth(memory_get_usage());
        $mem_allowed = ini_get('memory_limit');
        debug("%s: memory_get_usage() says %sBytes used. Limit is %s\n", $note, $mem_used,
            $mem_allowed);
    }
}

function weathermap_cron_part($value, $checkstring)
{
    // first, shortcut the most common cases - * and a simple single number
    if ($checkstring === '*') {
        return (true);
    }

    $v = stringval($value);

    if ($checkstring === $v) {
        return (true);
    }

    // Cron allows for multiple comma separated clauses, so let's break them
    // up first, and evaluate each one.
    $parts = explode(",", $checkstring);

    foreach ($parts as $part) {

        // just a number
        if ($part === $v) {
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

    return (false);
}

function weathermap_check_cron($time, $string)
{
    if ($string === '') {
        return (true);
    }

    if ($string === '*') {
        return (true);
    }

    $lt = localtime($time, true);
    list($minute, $hour, $wday, $day, $month) = preg_split('/\s+/', $string);

    $matched = true;

    $matched = $matched && weathermap_cron_part($lt['tm_min'], $minute);
    $matched = $matched && weathermap_cron_part($lt['tm_hour'], $hour);
    $matched = $matched && weathermap_cron_part($lt['tm_wday'], $wday);
    $matched = $matched && weathermap_cron_part($lt['tm_mday'], $day);
    $matched = $matched && weathermap_cron_part($lt['tm_mon'] + 1, $month);

    return ($matched);
}

function weathermap_run_maps($mydir)
{
    global $config;
    global $weathermap_debugging, $WEATHERMAP_VERSION;
    global $weathermap_map;
    global $weathermap_warncount;
    global $weathermap_poller_start_time;
    global $WM_config_keywords2;
    global $WM_config_keywords;
    global $weathermap_debug_suppress;
    global $weathermap_mem_highwater;

    $weathermap_mem_highwater = 0;

    if (true === function_exists('memory_get_usage')) {
        db_execute("replace into settings values('weathermap_initial_memory','"
            . memory_get_usage() . "')");
    }

    include_once $mydir . DIRECTORY_SEPARATOR . 'HTML_ImageMap.class.php';
    include_once $mydir . DIRECTORY_SEPARATOR . 'Weathermap.class.php';

    if (true === function_exists('memory_get_usage')) {
        db_execute("replace into settings values('weathermap_loaded_memory','"
            . memory_get_usage() . "')");
    }

    $total_warnings = 0;

    if (function_exists("microtime")) {
        $start_time = microtime(true);
    } else {
        $start_time = time();
    }

    if ($weathermap_poller_start_time === 0) {
        $weathermap_poller_start_time = $start_time;
    }

    $outdir = $mydir . DIRECTORY_SEPARATOR . 'output';
    $confdir = $mydir . DIRECTORY_SEPARATOR . 'configs';

    $mapcount = 0;

// take our debugging cue from the poller - turn on Poller debugging to get weathermap debugging
    if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_DEBUG) {
        $weathermap_debugging = true;
        $global_debug = true;
        $mode_message = 'DEBUG mode is on';
    } else {
        $global_debug = false;
        $mode_message
            = 'Normal logging mode. Turn on DEBUG in Cacti for more information';
    }
    $quietlogging = intval(read_config_option('weathermap_quiet_logging'));

// moved this outside the module_checks, so there should always be something in the logs!
    if ($quietlogging === 0) {
        cacti_log('Weathermap ' . $WEATHERMAP_VERSION . ' starting - ' . $mode_message
            . "\n", true, 'WEATHERMAP');
    }

    if (false === WM_module_checks()) {
        warn('Required modules for PHP Weathermap ' . $WEATHERMAP_VERSION
            . " were not present. Not running. [WMPOLL08]\n");
        return;
    }

    weathermap_memory_check('MEM Initial');
// move to the weathermap folder so all those relatives paths don't *have* to be absolute
    $orig_cwd = getcwd();
    chdir($mydir);

    db_execute("replace into settings values('weathermap_last_start_time','"
        . mysql_escape_string($start_time) . "')");

    // first, see if the output directory even exists
    if (false === is_dir($outdir)) {
        warn('Output directory (' . $outdir
            . ") doesn't exist!. No maps created. You probably need to create that directory, and make it writable by the poller process (like you did with the RRA directory) [WMPOLL07]\n");
        return;
    }

    // next, make sure that we stand a chance of writing files
    //// $testfile = realpath($outdir."weathermap.permissions.test");
    $testfile = $outdir . DIRECTORY_SEPARATOR . 'weathermap.permissions.test';
    $testfd = fopen($testfile, 'w');

    if (false === $testfd) {
        warn('Output directory (' . $outdir . ") isn't writable (tried to create '"
            . $testfile
            . "'). No maps created. You probably need to make it writable by the poller process (like you did with the RRA directory) [WMPOLL06]\n");

        return;
    }

    fclose($testfd);
    unlink($testfile);

    $queryrows =
        db_fetch_assoc(
            "select m.*, g.name as groupname from weathermap_maps m,weathermap_groups g where m.group_id=g.id and active='on' order by sortorder,id");

    if (true === is_array($queryrows)) {
        debug("Iterating all maps.\n");

        $imageformat = strtolower(read_config_option('weathermap_output_format'));
        $rrdtool_path = read_config_option('path_rrdtool');

        foreach ($queryrows as $map) {
            // reset the warning counter
            $weathermap_warncount = 0;
            // this is what will prefix log entries for this map
            $weathermap_map = '[Map ' . $map['id'] . '] ' . $map['configfile'];

            debug("FIRST TOUCH\n");

            if (true === weathermap_check_cron(intval($weathermap_poller_start_time),
                $map['schedule'])) {
                $mapfile = $confdir . DIRECTORY_SEPARATOR . $map['configfile'];
                $htmlfile = $outdir . DIRECTORY_SEPARATOR . $map['filehash'] . '.html';
                $statsfile = $outdir . DIRECTORY_SEPARATOR . $map['filehash'] . '.stats.xml';
                $resultsfile = $outdir . DIRECTORY_SEPARATOR . $map['filehash'] . '.results.txt';

                $imagefile = $outdir . DIRECTORY_SEPARATOR . $map['filehash'] . '.'
                    . $imageformat;
                $thumbimagefile =
                    $outdir . DIRECTORY_SEPARATOR . $map['filehash'] . '.thumb.'
                    . $imageformat;

                if (true === file_exists($mapfile)) {
                    if ($quietlogging === 0) {
                        warn('Map: ' . $mapfile . ' -> ' . $htmlfile . ' & ' . $imagefile
                            . "\n", true);
                    }
                    db_execute(
                        "replace into settings values('weathermap_last_started_file','"
                        . mysql_escape_string($weathermap_map) . "')");

                    if($global_debug === false && $map['debug']=='on') {
                        $weathermap_debugging = true;
                    }
                    else {
                        $weathermap_debugging = $global_debug;
                    }

                    if (function_exists("microtime")) {
                        $map_start = microtime(true);
                    } else {
                        $map_start = time();
                    }

                    weathermap_memory_check('MEM starting ' . $mapcount);
                    $wmap = new Weathermap;
                    $wmap->context = 'cacti';

                    // we can grab the rrdtool path from Cacti's config, in this case
                    $wmap->rrdtool = $rrdtool_path;

                    $wmap->ReadConfig($mapfile);

                    $wmap->add_hint('mapgroup', $map['groupname']);
                    $wmap->add_hint('mapgroupextra', ($map['group_id'] === 1
                        ? '' : $map['groupname']));

# in the order of precedence - global extras, group extras, and finally map extras
                    $queries = array ();
                    $queries[] =
                        'select * from weathermap_settings where mapid=0 and groupid=0';
                    $queries[] =
                        'select * from weathermap_settings where mapid=0 and groupid='
                        . intval($map['group_id']);
                    $queries[] = 'select * from weathermap_settings where mapid='
                        . intval($map['id']);

                    foreach ($queries as $sql) {
                        $settingrows = db_fetch_assoc($sql);

                        if ((true === is_array($settingrows)) && count($settingrows) > 0)
                            {
                            foreach ($settingrows as $setting) {
                                if ($setting['mapid'] === 0 && $setting['groupid'] === 0)
                                    {
                                    debug('Setting additional (all maps) option: '
                                        . $setting['optname'] . " to '"
                                        . $setting['optvalue'] . "'\n");
                                    $wmap->add_hint($setting['optname'],
                                        $setting['optvalue']);
                                } elseif ($setting['groupid'] != 0) {
                                    debug(
                                        'Setting additional (all maps in group) option: '
                                        . $setting['optname'] . " to '"
                                        . $setting['optvalue'] . "'\n");
                                    $wmap->add_hint($setting['optname'],
                                        $setting['optvalue']);
                                } else {
                                    debug('Setting additional map-global option: '
                                        . $setting['optname'] . " to '"
                                        . $setting['optvalue'] . "'\n");
                                    $wmap->add_hint($setting['optname'],
                                        $setting['optvalue']);
                                }
                            }
                        }
                    }

                    weathermap_memory_check('MEM postread ' . $mapcount);
                    $wmap->ReadData();
                    weathermap_memory_check('MEM postdata ' . $mapcount);

                    $configured_imageuri = $wmap->imageuri;
                    $wmap->imageuri = 'weathermap-cacti-plugin.php?action=viewimage&id='
                        . $map['filehash'] . '&time=' . time();

                    if ($quietlogging === 0) {
                        warn(
                            "About to write image file. If this is the last message in your log, increase memory_limit in php.ini [WMPOLL01]\n",
                            true);
                    }
                    weathermap_memory_check('MEM pre-render ' . $mapcount);

                    $wmap->DrawMap($imagefile, $thumbimagefile,
                        read_config_option('weathermap_thumbsize'));

                    if ($quietlogging === 0) {
                        warn('Wrote map to ' . $imagefile . ' and ' . $thumbimagefile
                            . "\n", true);
                    }

                    $fd = @fopen($htmlfile, 'w');

                    if ($fd !== false) {
                        fwrite($fd,
                            $wmap->MakeHTML('weathermap_' . $map['filehash'] . '_imap'));
                        fclose($fd);
                        debug("Wrote HTML to %s\n", $htmlfile);
                    } else {
                        if (true === file_exists($htmlfile)) {
                            warn('Failed to overwrite ' . $htmlfile
                                . " - permissions of existing file are wrong? [WMPOLL02]\n");
                        } else {
                            warn('Failed to create ' . $htmlfile
                                . " - permissions of output directory are wrong? [WMPOLL03]\n");
                        }
                    }

                    // put back the configured imageuri
                    $wmap->imageuri = $configured_imageuri;

                    // if an htmloutputfile was configured, output the HTML there too
                    // but using the configured imageuri and imagefilename
                    if($wmap->htmloutputfile != "") {
                        $htmlfile = $wmap->htmloutputfile;
                        $fd = @fopen($htmlfile, 'w');

                        if ($fd !== false) {
                            fwrite($fd,
                                $wmap->MakeHTML('weathermap_' . $map['filehash'] . '_imap'));
                            fclose($fd);
                            debug("Wrote HTML to %s\n", $htmlfile);
                        } else {
                            if (true === file_exists($htmlfile)) {
                                warn('Failed to overwrite ' . $htmlfile
                                    . " - permissions of existing file are wrong? [WMPOLL02]\n");
                            } else {
                                warn('Failed to create ' . $htmlfile
                                    . " - permissions of output directory are wrong? [WMPOLL03]\n");
                            }
                        }
                    }

                    if($wmap->imageoutputfile != "" && $wmap->imageoutputfile != "weathermap.png") {
                        // TODO - copy the existing file to the configured location too
                        copy($imagefile, $wmap->imageoutputfile);
                    }
                    
                    $wmap->DumpStats($statsfile);
                    $wmap->WriteDataFile($resultsfile);

                    $processed_title = $wmap->ProcessString($wmap->title, $wmap);

                    db_execute("update weathermap_maps set titlecache='"
                        . mysql_real_escape_string($processed_title) . "' where id="
                        . intval($map['id']));

                    if (intval($wmap->thumb_width) > 0) {
                        db_execute('update weathermap_maps set thumb_width='
                            . intval($wmap->thumb_width) . ', thumb_height='
                            . intval($wmap->thumb_height) . ' where id='
                            . intval($map['id']));
                    }
                    $wmap->CleanUp();
                    unset($wmap);

                    if (function_exists("microtime")) {
                        $map_end = microtime(true);
                    } else {
                        $map_end = time();
                    }

                    $map_duration = $map_end - $map_start;
                    debug("TIME: %s took %f seconds.\n", $mapfile, $map_duration);

                    weathermap_memory_check('MEM after ' . $mapcount);
                    $mapcount++;
                    db_execute(
                        "replace into settings values('weathermap_last_finished_file','"
                        . mysql_escape_string($weathermap_map) . "')");
                } else {
                    warn('Mapfile ' . $mapfile
                        . " is not readable or doesn't exist [WMPOLL04]\n");
                }
                // if the debug mode was set to once for this map, then that
                // time has now passed, and it can be turned off again.
                $newdebug = $map['debug'];
                if($newdebug == 'once') {
                    $newdebug = 'off';
                }
                db_execute(sprintf(
                    "update weathermap_maps set warncount=%d, runtime=%f, debug='%s' where id=%d",
                    $weathermap_warncount, $map_duration, $newdebug, $map['id']));
                $total_warnings += $weathermap_warncount;
                $weathermap_warncount = 0;
                $weathermap_map = '';
            } else {
                debug('Skipping ' . $map['id'] . ' (' . $map['configfile']
                    . ") due to schedule.\n");
            }
        }
        debug("Iterated all %d maps.\n", $mapcount);
    } else {
        if ($quietlogging === 0) {
            warn("No activated maps found. [WMPOLL05]\n");
        }
    }

    weathermap_memory_check('MEM Final');
    chdir($orig_cwd);

    if (function_exists("microtime")) {
        $end_time = microtime(true);
    } else {
        $end_time = time();
    }

    $duration = $end_time - $start_time;

    $stats_string =
        sprintf('%s: %d maps were run in %f seconds with %d warnings', date(DATE_RFC822),
            $mapcount, $duration, $total_warnings);

    if ($quietlogging === 0) {
        warn('STATS: Weathermap ' . $WEATHERMAP_VERSION . ' run complete - '
            . $stats_string . "\n", true);
    }

    db_execute("replace into settings values('weathermap_last_stats','"
        . mysql_escape_string($stats_string) . "')");
    db_execute("replace into settings values('weathermap_last_map_count','"
        . mysql_escape_string($mapcount) . "')");
    db_execute("replace into settings values('weathermap_last_finish_time','"
        . mysql_escape_string($end_time) . "')");

    if (true === function_exists('memory_get_usage')) {
        db_execute("replace into settings values('weathermap_final_memory','"
            . memory_get_usage() . "')");
        db_execute("replace into settings values('weathermap_highwater_memory','"
            . $weathermap_mem_highwater . "')");
    }

    if (true === function_exists("memory_get_peak_usage")) {
        db_execute("replace into settings values('weathermap_peak_memory','"
            . memory_get_peak_usage() . "')");
    }
}

// vim:ts=4:sw=4:
?>
