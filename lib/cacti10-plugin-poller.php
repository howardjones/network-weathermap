<?php

// figure out if this poller run is hitting the 'cron' entry for any maps.
function weathermap_poller_top()
{
    global $weathermap_poller_start_time;

    $n = time();

    // round to the nearest minute, since that's all we need for the crontab-style stuff
    $weathermap_poller_start_time = $n - ($n % 60);
}

function weathermap_poller_output($rrd_update_array)
{
    global $config;

    $pdo = weathermap_get_pdo();

    $weathermap_data_update = $pdo->prepare("\"UPDATE weathermap_data SET last_time=?, last_calc='?', last_value='?',sequence=sequence+1  where id = ?");

    $log_verbosity = read_config_option("log_verbosity");

    if ($log_verbosity >= POLLER_VERBOSITY_DEBUG) {
        cacti_log("WM poller_output: STARTING\n", true, "WEATHERMAP");
    }

    // partially borrowed from Jimmy Conner's THold plugin.
    // (although I do things slightly differently - I go from filenames, and don't use the poller_interval)

    // new version works with *either* a local_data_id or rrdfile in the weathermap_data table, and returns BOTH

    $stmt = $pdo->query("SELECT DISTINCT weathermap_data.id, weathermap_data.last_value, 
		weathermap_data.last_time, weathermap_data.data_source_name, 
		data_template_data.data_source_path, data_template_data.local_data_id, 
		data_template_rrd.data_source_type_id 
		FROM weathermap_data, data_template_data, data_template_rrd 
		WHERE weathermap_data.local_data_id=data_template_data.local_data_id 
		AND data_template_rrd.local_data_id=data_template_data.local_data_id 
		AND weathermap_data.local_data_id<>0");

    $requiredlist = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $path_rra = $config["rra_path"];

    # especially on Windows, it seems that filenames are not reliable (sometimes \ and sometimes / even though path_rra is always /) .
    # let's make an index from local_data_id to filename, and then use local_data_id as the key...

    foreach (array_keys($rrd_update_array) as $key) {
        if (isset($rrd_update_array[$key]['times']) && is_array($rrd_update_array[$key]['times'])) {
            $knownfiles[$rrd_update_array[$key]["local_data_id"]] = $key;
        }
    }

    foreach ($requiredlist as $required) {
        $file = str_replace("<path_rra>", $path_rra, $required['data_source_path']);
        $dsname = $required['data_source_name'];
        $local_data_id = $required['local_data_id'];

        if (isset($knownfiles[$local_data_id])) {
            $file2 = $knownfiles[$local_data_id];
            if ($file2 != '') {
                $file = $file2;
            }
        }

        if ($log_verbosity >= POLLER_VERBOSITY_DEBUG) {
            cacti_log("WM poller_output: Looking for $file ($local_data_id) (" . $required['data_source_path'] . ")\n",
                true, "WEATHERMAP");
        }

        if (isset($rrd_update_array[$file]) && is_array($rrd_update_array[$file]) && isset($rrd_update_array[$file]['times']) && is_array($rrd_update_array[$file]['times']) && isset($rrd_update_array{$file}['times'][key($rrd_update_array[$file]['times'])]{$dsname})) {
            $value = $rrd_update_array{$file}['times'][key($rrd_update_array[$file]['times'])]{$dsname};
            $time = key($rrd_update_array[$file]['times']);
            if ($log_verbosity >= POLLER_VERBOSITY_MEDIUM) {
                cacti_log("WM poller_output: Got one! $file:$dsname -> $time $value\n", true, "WEATHERMAP");
            }

            $period = $time - $required['last_time'];
            $lastval = $required['last_value'];

            // if the new value is a NaN, we'll give 0 instead, and pretend it didn't happen from the point
            // of view of the counter etc. That way, we don't get those enormous spikes. Still doesn't deal with
            // reboots very well, but it should improve it for drops.
            if ($value == 'U') {
                $newvalue = 0;
                $newlastvalue = $lastval;
                $newtime = $required['last_time'];
            } else {
                $newlastvalue = $value;
                $newtime = $time;

                switch ($required['data_source_type_id']) {
                    case 1: //GAUGE
                        $newvalue = $value;
                        break;

                    case 2: //COUNTER
                        if ($value >= $lastval) {
                            // Everything is normal
                            $newvalue = $value - $lastval;
                        } else {
                            // Possible overflow, see if its 32bit or 64bit
                            if ($lastval > 4294967295) {
                                $newvalue = (18446744073709551615 - $lastval) + $value;
                            } else {
                                $newvalue = (4294967295 - $lastval) + $value;
                            }
                        }
                        $newvalue = $newvalue / $period;
                        break;

                    case 3: //DERIVE
                        $newvalue = ($value - $lastval) / $period;
                        break;

                    case 4: //ABSOLUTE
                        $newvalue = $value / $period;
                        break;

                    default: // do something somewhat sensible in case something odd happens
                        $newvalue = $value;
                        wm_warn("poller_output found an unknown data_source_type_id for $file:$dsname");
                        break;
                }
            }

            // db_execute("UPDATE weathermap_data SET last_time=$newtime, last_calc='$newvalue', last_value='$newlastvalue',sequence=sequence+1  where id = " . $required['id']);

            $weathermap_data_update->execute(array($newtime, $newvalue, $newlastvalue, $required['id']));
            if ($log_verbosity >= POLLER_VERBOSITY_DEBUG) {
                cacti_log("WM poller_output: Final value is $newvalue (was $lastval, period was $period)\n", true, "WEATHERMAP");
            }
        }
    }

    if ($log_verbosity >= POLLER_VERBOSITY_DEBUG) {
        cacti_log("WM poller_output: ENDING\n", true, "WEATHERMAP");
    }

    return $rrd_update_array;
}

function weathermap_poller_bottom()
{
    global $config;
    global $WEATHERMAP_VERSION;

    include_once($config["library_path"] . DIRECTORY_SEPARATOR . "database.php");
    include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "poller-common.php");

    $pdo = weathermap_get_pdo();

    weathermap_setup_table();

    $renderperiod = read_config_option("weathermap_render_period");
    $rendercounter = read_config_option("weathermap_render_counter");
    $quietlogging = read_config_option("weathermap_quiet_logging");

    if ($renderperiod < 0) {
        // manual updates only
        if ($quietlogging == 0) {
            cacti_log("Weathermap $WEATHERMAP_VERSION - no updates ever", true, "WEATHERMAP");
        }
        return;
    } else {
        // if we're due, run the render updates
        if (($renderperiod == 0) || (($rendercounter % $renderperiod) == 0)) {
            weathermap_run_maps(dirname(dirname(__FILE__)));
        } else {
            if ($quietlogging == 0) {
                cacti_log("Weathermap $WEATHERMAP_VERSION - no update in this cycle ($rendercounter)", true,
                    "WEATHERMAP");
            }
        }
        // increment the counter
        $newcount = ($rendercounter + 1) % 1000;
        $statement = $pdo->prepare("REPLACE INTO settings VALUES('weathermap_render_counter',?)");
        $statement->execute(array($newcount));
    }
}
