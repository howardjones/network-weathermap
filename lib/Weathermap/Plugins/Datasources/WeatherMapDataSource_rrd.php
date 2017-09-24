<?php
// RRDtool datasource plugin.
//     gauge:filename.rrd:ds_in:ds_out
//     filename.rrd:ds_in:ds_out
//     filename.rrd:ds_in:ds_out
//

//include_once dirname(__FILE__) . "/../DatasourceUtility.php";

namespace Weathermap\Plugins\Datasources;

use Weathermap\Core\MapUtility;

class WeatherMapDataSource_rrd extends DatasourceBase
{

    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array(
            '/^(.*\.rrd):([\-a-zA-Z0-9_]+):([\-a-zA-Z0-9_]+)$/',
            '/^(.*\.rrd)$/'
        );
        $this->name = "RRD";
    }

    public function init(&$map)
    {
        global $config;

        if ($map->context == 'cacti') {
            MapUtility::wm_debug("RRD DS: path_rra is " . $config["rra_path"] . " - your rrd pathname must be exactly this to use poller_output\n");
            // save away a couple of useful global SET variables
            $map->add_hint("cacti_path_rra", $config["rra_path"]);
            $map->add_hint("cacti_url", $config['url_path']);
        }
        if (file_exists($map->rrdtool)) {
            if ((function_exists('is_executable')) && (!is_executable($map->rrdtool))) {
                MapUtility::wm_warn("RRD DS: RRDTool exists but is not executable? [WMRRD01]\n");
                return false;
            }
            $map->rrdtool_check = "FOUND";
            return true;
        }
        // normally, DS plugins shouldn't really pollute the logs
        // this particular one is important to most users though...
        if ($map->context == 'cli') {
            MapUtility::wm_warn("RRD DS: Can't find RRDTOOL. Check line 29 of the 'weathermap' script.\nRRD-based TARGETs will fail. [WMRRD02]\n");
        }
        if ($map->context == 'cacti') {    // unlikely to ever occur
            MapUtility::wm_warn("RRD DS: Can't find RRDTOOL. Check your Cacti config. [WMRRD03]\n");
        }

        return false;
    }

    private function wmrrd_read_from_poller_output($rrdfile, $cf, $start, $end, $dsnames, &$map, &$item)
    {
        global $config;
        $pdo = weathermap_get_pdo();

        MapUtility::wm_debug("RRD ReadData: poller_output style\n");

        if (!isset($config)) {
            MapUtility::wm_warn("RRD ReadData: poller_output - Cacti environment is not right [WMRRD12]\n");
        }

        // take away the cacti bit, to get the appropriate path for the table
        $path_rra = $config["rra_path"];
        $db_rrdname = $rrdfile;
        $db_rrdname = str_replace($path_rra, "<path_rra>", $db_rrdname);
        MapUtility::wm_debug("******************************************************************\nChecking weathermap_data\n");

        foreach (array(IN, OUT) as $dir) {
            MapUtility::wm_debug("RRD ReadData: poller_output - looking for $dir value\n");
            if ($dsnames[$dir] != '-') {
                MapUtility::wm_debug("RRD ReadData: poller_output - DS name is " . $dsnames[$dir] . "\n");

                $SQL = "select * from weathermap_data where rrdfile=" . $pdo->quote($db_rrdname) . " and data_source_name=" . $pdo->quote($dsnames[$dir]);

                $SQLcheck = "select data_template_data.local_data_id from data_template_data,data_template_rrd where data_template_data.local_data_id=data_template_rrd.local_data_id and data_template_data.data_source_path=" . $pdo->quote($db_rrdname) . " and data_template_rrd.data_source_name=" . $pdo->quote($dsnames[$dir]);
                $SQLvalid = "select data_template_rrd.data_source_name from data_template_data,data_template_rrd where data_template_data.local_data_id=data_template_rrd.local_data_id and data_template_data.data_source_path=" . $pdo->quote($db_rrdname);

                $worst_time = time() - 8 * 60;
                $result = db_fetch_row($SQL);
                // OK, the straightforward query for data failed, let's work out why, and add the new data source if necessary
                if (!isset($result['id'])) {
                    MapUtility::wm_debug("RRD ReadData: poller_output - Adding new weathermap_data row for $db_rrdname:" . $dsnames[$dir] . "\n");
                    $result = db_fetch_row($SQLcheck);
                    if (!isset($result['local_data_id'])) {
                        $fields = array();
                        $results = db_fetch_assoc($SQLvalid);
                        foreach ($results as $result) {
                            $fields[] = $result['data_source_name'];
                        }
                        if (count($fields) > 0) {
                            MapUtility::wm_warn("RRD ReadData: poller_output: " . $dsnames[$dir] . " is not a valid DS name for $db_rrdname - valid names are: " . join(", ", $fields) . " [WMRRD07]\n");
                        } else {
                            MapUtility::wm_warn("RRD ReadData: poller_output: $db_rrdname is not a valid RRD filename within this Cacti install. <path_rra> is $path_rra [WMRRD08]\n");
                        }
                    } else {
                        // add the new data source (which we just checked exists) to the table.
                        // Include the local_data_id as well, to make life easier in poller_output
                        // (and to allow the cacti: DS plugin to use the same table, too)
                        $SQLins = "insert into weathermap_data (rrdfile, data_source_name, sequence, local_data_id) values (" . $pdo->quote($db_rrdname) . "," . $pdo->quote($dsnames[$dir]) . ", 0," . $result['local_data_id'] . ")";
                        MapUtility::wm_debug("RRD ReadData: poller_output - Adding new weathermap_data row for data source ID " . $result['local_data_id'] . "\n");
                        db_execute($SQLins);
                    }
                } else {    // the data table line already exists
                    MapUtility::wm_debug("RRD ReadData: poller_output - found weathermap_data row\n");
                    // if the result is valid, then use it
                    if (($result['sequence'] > 2) && ($result['last_time'] > $worst_time)) {
                        $this->data[$dir] = $result['last_calc'];
                        $this->dataTime = $result['last_time'];
                        MapUtility::wm_debug("RRD ReadData: poller_output - data looks valid\n");
                    } else {
                        $this->data[$dir] = 0.0;
                        MapUtility::wm_debug("RRD ReadData: poller_output - data is either too old, or too new\n");
                    }
                    // now, we can use the local_data_id to get some other useful info
                    // first, see if the weathermap_data entry *has* a local_data_id. If not, we need to update this entry.
                    $ldi = 0;
                    if (!isset($result['local_data_id']) || $result['local_data_id'] == 0) {
                        $r2 = db_fetch_row($SQLcheck);
                        if (isset($r2['local_data_id'])) {
                            $ldi = $r2['local_data_id'];
                            MapUtility::wm_debug("RRD ReadData: updated  local_data_id for wmdata.id=" . $result['id'] . "to $ldi\n");
                            // put that in now, so that we can skip this step next time
                            db_execute("update weathermap_data set local_data_id=" . $r2['local_data_id'] . " where id=" . $result['id']);
                        }
                    } else {
                        $ldi = $result['local_data_id'];
                    }

                    // fill all that other information (ifSpeed, etc)
                    // (but only if it's not switched off!)
                    if (($map->get_hint("rrdtool_no_cacti_extras") === null) && $ldi > 0) {
                        updateCactiData($item, $ldi);
                    }
                }
            } else {
                MapUtility::wm_debug("RRD ReadData: poller_output - DS name is '-'\n");
            }
        }

        MapUtility::wm_debug("RRD ReadData: poller_output - result is " . ($this->data[IN] === null ? 'null' : $this->data[IN]) . "," . ($this->data[OUT] === null ? 'null' : $this->data[OUT]) . "\n");
        MapUtility::wm_debug("RRD ReadData: poller_output - ended\n");
    }


    # rrdtool graph /dev/null -f "" -s now-30d -e now DEF:in=../rra/atm-sl_traffic_in_5498.rrd:traffic_in:AVERAGE DEF:out=../rra/atm-sl_traffic_in_5498.rrd:traffic_out:AVERAGE VDEF:avg_in=in,AVERAGE VDEF:avg_out=out,AVERAGE PRINT:avg_in:%lf PRINT:avg_out:%lf

    private function wmrrd_read_from_real_rrdtool_aggregate($rrdfile, $cf, $aggregatefn, $start, $end, $dsnames, &$map, &$item)
    {
        global $php_errormsg;

        MapUtility::wm_debug("RRD ReadData: VDEF style, for " . $item->my_type() . " " . $item->name . "\n");

        $extra_options = $map->get_hint("rrd_options");

        // Assemble an array of command args.
        // In a real programming language, we'd be able to pass this directly to exec()
        // However, this will at least allow us to put quotes around args that need them
        $args = array();
        $args[] = "graph";
        $args[] = "/dev/null";
        $args[] = "-f";
        $args[] = "''";
        $args[] = "--start";
        $args[] = $start;
        $args[] = "--end";
        $args[] = $end;

        # assemble an appropriate RRDtool command line, skipping any '-' DS names.
        # $command = $map->rrdtool . " graph /dev/null -f ''  --start $start --end $end ";

        if ($dsnames[IN] != '-') {
            $args[] = "DEF:in=$rrdfile:" . $dsnames[IN] . ":$cf";
            $args[] = "VDEF:agg_in=in,$aggregatefn";
            $args[] = "PRINT:agg_in:'IN %lf'";
        }

        if ($dsnames[OUT] != '-') {
            $args[] = "DEF:out=$rrdfile:" . $dsnames[OUT] . ":$cf";
            $args[] = "VDEF:agg_out=out,$aggregatefn";
            $args[] = "PRINT:agg_out:'OUT %lf'";
        }

        $command = $map->rrdtool;
        foreach ($args as $arg) {
            if (strchr($arg, " ") != false) {
                $command .= ' "' . $arg . '"';
            } else {
                $command .= ' ' . $arg;
            }
        }
        $command .= " " . $extra_options;

        MapUtility::wm_debug("RRD ReadData: Running: $command\n");
        $pipe = popen($command, "r");

        $lines = array();

        if (!isset($pipe)) {
            MapUtility::wm_warn("RRD Aggregate ReadData: failed to open pipe to RRDTool: " . $php_errormsg . " [WMRRD04]\n");
            return;
        }

        $buffer = '';
        $data_ok = false;

        while (!feof($pipe)) {
            $line = fgets($pipe, 4096);
            // there might (pre-1.5) or might not (1.5+) be a leading blank line
            // we don't want to count it if there is
            if (trim($line) != "") {
                MapUtility::wm_debug("> " . $line);
                $buffer .= $line;
                $lines[] = $line;
            }
        }
        pclose($pipe);

        if (sizeof($lines) == 0) {
            MapUtility::wm_warn("RRD Aggregate ReadData: Not enough output from RRDTool (0 lines). [WMRRD09]\n");
            return;
        }

        foreach ($lines as $line) {
            if (preg_match('/^\'(IN|OUT)\s(\-?\d+[\.,]?\d*e?[+-]?\d*:?)\'$/i', $line, $matches)) {
                MapUtility::wm_debug("MATCHED: " . $matches[1] . " " . $matches[2] . "\n");
                if ($matches[1] == 'IN') {
                    $this->data[IN] = floatval($matches[2]);
                }
                if ($matches[1] == 'OUT') {
                    $this->data[OUT] = floatval($matches[2]);
                }
                $data_ok = true;
            }
        }

        if ($data_ok) {
            if ($this->data[IN] === null) {
                $this->data[IN] = 0.0;
            }
            if ($this->data[OUT] === null) {
                $this->data[OUT] = 0.0;
            }
        }

        MapUtility::wm_debug("RRD ReadDataFromRealRRDAggregate: Returning (" . ($this->data[IN] === null ? 'null' : $this->data[IN]) . "," . ($this->data[OUT] === null ? 'null' : $this->data[OUT]) . ",$this->dataTime)\n");
    }

    private function wmrrd_read_from_real_rrdtool($rrdfile, $cf, $start, $end, $dsnames, &$map, &$item)
    {
        MapUtility::wm_debug("RRD ReadData: traditional style\n");

        // we get the last 800 seconds of data - this might be 1 or 2 lines, depending on when in the
        // cacti polling cycle we get run. This ought to stop the 'some lines are grey' problem that some
        // people were seeing

        // NEW PLAN - READ LINES (LIKE NOW), *THEN* CHECK IF REQUIRED DS NAMES EXIST (AND FAIL IF NOT),
        //     *THEN* GET THE LAST LINE WHERE THOSE TWO DS ARE VALID, *THEN* DO ANY PROCESSING.
        //  - this allows for early failure, and also tolerance of empty data in other parts of an rrd (like smokeping uptime)

        $extra_options = $map->get_hint("rrd_options");

        $values = array();
        $args = array();

        $args[] = "fetch";
        $args[] = $rrdfile;
        $args[] = $cf;
        $args[] = "--start";
        $args[] = $start;
        $args[] = "--end";
        $args[] = $end;

        $command = $map->rrdtool;
        foreach ($args as $arg) {
            if (strchr($arg, " ") != false) {
                $command .= ' "' . $arg . '"';
            } else {
                $command .= ' ' . $arg;
            }
        }
        $command .= " " . $extra_options;

        MapUtility::wm_debug("RRD ReadData: Running: $command\n");
        $pipe = popen($command, "r");

        $lines = array();
        $linecount = 0;

        if (!isset($pipe)) {
            MapUtility::wm_warn("RRD ReadData: failed to open pipe to RRDTool: " . $php_errormsg . " [WMRRD04]\n");
            return;
        }
        $headings = fgets($pipe, 4096);
        // this replace fudges 1.2.x output to look like 1.0.x
        // then we can treat them both the same.
        $heads = preg_split('/\s+/', preg_replace('/^\s+/', "timestamp ", $headings));

        $buffer = '';

        while (!feof($pipe)) {
            $line = fgets($pipe, 4096);
            // there might (pre-1.5) or might not (1.5+) be a leading blank line
            // we don't want to count it if there is
            if (trim($line) != "") {
                MapUtility::wm_debug("> " . $line);
                $buffer .= $line;
                $lines[] = $line;
                $linecount++;
            }
        }
        pclose($pipe);

        MapUtility::wm_debug("RRD ReadData: Read $linecount lines from rrdtool\n");
        MapUtility::wm_debug("RRD ReadData: Headings are: $headings\n");

        if ((in_array($dsnames[IN], $heads) || $dsnames[IN] == '-') && (in_array($dsnames[OUT], $heads) || $dsnames[OUT] == '-')) {
            // deal with the data, starting with the last line of output
            $rlines = array_reverse($lines);

            foreach ($rlines as $line) {
                MapUtility::wm_debug("--" . $line . "\n");
                $cols = preg_split('/\s+/', $line);
                for ($i = 0, $cnt = count($cols) - 1; $i < $cnt; $i++) {
                    $h = $heads[$i];
                    $v = $cols[$i];
                    $values[$h] = trim($v);
                }

                $data_ok = false;

                foreach (array(IN, OUT) as $dir) {
                    $n = $dsnames[$dir];
                    if (array_key_exists($n, $values)) {
                        $candidate = $values[$n];
                        if (preg_match('/^\-?\d+[\.,]?\d*e?[+-]?\d*:?$/i', $candidate)) {
                            $this->data[$dir] = $candidate;
                            MapUtility::wm_debug("$candidate is OK value for $n\n");
                            $data_ok = true;
                        }
                    }
                }

                if ($data_ok) {
                    // at least one of the named DS had good data
                    $this->dataTime = intval($values['timestamp']);

                    // 'fix' a -1 value to 0, so the whole thing is valid
                    // (this needs a proper fix!)
                    if ($this->data[IN] === null) {
                        $this->data[IN] = 0.0;
                    }
                    if ($this->data[OUT] === null) {
                        $this->data[OUT] = 0.0;
                    }

                    // break out of the loop here
                    break;
                }
            }
        } else {
            // report DS name error
            $names = join(",", $heads);
            $names = str_replace("timestamp,", "", $names);
            MapUtility::wm_warn("RRD ReadData: At least one of your DS names (" . $dsnames[IN] . " and " . $dsnames[OUT] . ") were not found, even though there was a valid data line. Maybe they are wrong? Valid DS names in this file are: $names [WMRRD06]\n");
        }
        MapUtility::wm_debug("RRD ReadDataFromRealRRD: Returning (" . ($this->data[IN] === null ? 'null' : $this->data[IN]) . "," . ($this->data[OUT] === null ? 'null' : $this->data[OUT]) . ",$this->dataTime)\n");
    }

    // Actually read data from a data source, and return it
    // returns a 3-part array (invalue, outvalue and datavalid time_t)
    // invalue and outvalue should be -1,-1 if there is no valid data
    // data_time is intended to allow more informed graphing in the future
    public function readData($targetstring, &$map, &$item)
    {
        $this->data[IN] = null;
        $this->data[OUT] = null;
        $dsnames = array(IN=>"traffic_in", OUT=>"traffic_out");

        $rrdfile = $targetstring;

        if ($map->get_hint("rrd_default_in_ds") != '') {
            $dsnames[IN] = $map->get_hint("rrd_default_in_ds");
            MapUtility::wm_debug("Default 'in' DS name changed to " . $dsnames[IN] . ".\n");
        }
        if ($map->get_hint("rrd_default_out_ds") != '') {
            $dsnames[OUT] = $map->get_hint("rrd_default_out_ds");
            MapUtility::wm_debug("Default 'out' DS name changed to " . $dsnames[OUT] . ".\n");
        }

        $multiplier = 8; // default bytes-to-bits

        if (preg_match('/^(.*\.rrd):([\-a-zA-Z0-9_]+):([\-a-zA-Z0-9_]+)$/', $targetstring, $matches)) {
            $rrdfile = $matches[1];

            $dsnames[IN] = $matches[2];
            $dsnames[OUT] = $matches[3];

            MapUtility::wm_debug("Special DS names seen (" . $dsnames[IN] . " and " . $dsnames[OUT] . ").\n");
        }

        if (preg_match("/^rrd:(.*)/", $rrdfile, $matches)) {
            $rrdfile = $matches[1];
        }

        if (preg_match("/^gauge:(.*)/", $rrdfile, $matches)) {
            $rrdfile = $matches[1];
            $multiplier = 1;
        }

        if (preg_match('/^scale:([+-]?\d*\.?\d*):(.*)/', $rrdfile, $matches)) {
            $rrdfile = $matches[2];
            $multiplier = $matches[1];
        }

        MapUtility::wm_debug("SCALING result by $multiplier\n");

        // try and make a complete path, if we've been given a clue
        // (if the path starts with a . or a / then assume the user knows what they are doing)
        if (!preg_match('/^(\/|\.)/', $rrdfile)) {
            $rrdbase = $map->get_hint('rrd_default_path');
            if ($rrdbase != '') {
                $rrdfile = $rrdbase . "/" . $rrdfile;
            }
        }

        $cfname = intval($map->get_hint('rrd_cf'));
        if ($cfname == '') {
            $cfname = 'AVERAGE';
        }

        $period = intval($map->get_hint('rrd_period'));
        if ($period == 0) {
            $period = 800;
        }
        $start = $map->get_hint('rrd_start');
        if ($start == '') {
            $start = "now-$period";
            $end = "now";
        } else {
            $end = "start+" . $period;
        }

        $use_poller_output = intval($map->get_hint('rrd_use_poller_output'));
        $nowarn_po_agg = intval($map->get_hint("nowarn_rrd_poller_output_aggregation"));
        $aggregatefunction = $map->get_hint('rrd_aggregate_function');

        if ($aggregatefunction != '' && $use_poller_output == 1) {
            $use_poller_output = 0;
            if ($nowarn_po_agg == 0) {
                MapUtility::wm_warn("Can't use poller_output for rrd-aggregated data - disabling rrd_use_poller_output [WMRRD10]\n");
            }
        }

        if ($use_poller_output == 1) {
            MapUtility::wm_debug("Going to try poller_output, as requested.\n");
            WeatherMapDataSource_rrd::wmrrd_read_from_poller_output($rrdfile, "AVERAGE", $start, $end, $dsnames, $map, $item);
        }

        // if poller_output didn't get anything, or if it couldn't/didn't run, do it the old-fashioned way
        // - this will still be the case for the first couple of runs after enabling poller_output support
        //   because there won't be valid data in the weathermap_data table yet.
        if (($dsnames[IN] != '-' && $this->data[IN] === null) || ($dsnames[OUT] != '-' && $this->data[OUT] === null)) {
            if ($use_poller_output == 1) {
                MapUtility::wm_debug("poller_output didn't get anything useful. Kicking it old skool.\n");
            }
            if (file_exists($rrdfile)) {
                MapUtility::wm_debug("RRD ReadData: Target DS names are " . $dsnames[IN] . " and " . $dsnames[OUT] . "\n");

                if ($aggregatefunction != '') {
                    WeatherMapDataSource_rrd::wmrrd_read_from_real_rrdtool_aggregate($rrdfile, $cfname, $aggregatefunction, $start, $end, $dsnames, $map, $item);
                } else {
                    // do this the tried and trusted old-fashioned way
                    WeatherMapDataSource_rrd::wmrrd_read_from_real_rrdtool($rrdfile, $cfname, $start, $end, $dsnames, $map, $item);
                }
            } else {
                MapUtility::wm_warn("Target $rrdfile doesn't exist. Is it a file? [WMRRD06]\n");
            }
        }

        // if the Locale says that , is the decimal point, then rrdtool
        // will honour it. However, floatval() doesn't, so let's replace
        // any , with . (there are never thousands separators, luckily)
        //
        if ($this->data[IN] !== null) {
            $this->data[IN] = $multiplier * floatval(str_replace(",", ".", $this->data[IN]));
        }
        if ($this->data[OUT] !== null) {
            $this->data[OUT] = $multiplier * floatval(str_replace(",", ".", $this->data[OUT]));
        }

        return $this->returnData();
    }
}

// vim:ts=4:sw=4:
