<?php

//include_once dirname(__FILE__) . "/../DatasourceUtility.php";

namespace Weathermap\Plugins\Datasources;

class WeatherMapDataSource_dsstats extends DatasourceBase
{

    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array(
            '/^dsstats:([a-z]+):(\d+):([\-a-zA-Z0-9_]+):([\-a-zA-Z0-9_]+)$/',
            '/^dsstats:(\d+):([\-a-zA-Z0-9_]+):([\-a-zA-Z0-9_]+)$/'
        );

        $this->name = "CactiDSStats";
    }

    public function init(&$map)
    {
        if ($map->context == 'cacti') {
            if (!function_exists('db_fetch_assoc')) {
                wm_debug("ReadData DSStats: Cacti database library not found. [DSSTATS001]\n");
                return false;
            }

            $dsstats_running = false;

            wm_debug("ReadData DSStats: Checking for 1.x integrated...\n");
            if (function_exists("read_config_option")) {
                if (read_config_option("dsstats_enable") == "on") {
                    $dsstats_running = true;
                    wm_debug("ReadData DSStats: Found 1.x integrated DSStats\n");
                } else {
                    wm_debug("ReadData DSStats: No 1.x integrated DSStats\n");
                }
            }

            if (!$dsstats_running) {
                wm_debug("ReadData DSStats: Checking for 0.8.8 plugin...\n");
                if (function_exists("api_plugin_is_enabled")) {
                    if (api_plugin_is_enabled('dsstats')) {
                        wm_debug("ReadData DSStats: DSStats plugin enabled. [DSSTATS002B]\n");
                        $dsstats_running = true;
                    } else {
                        wm_debug("ReadData DSStats: DSStats plugin NOT enabled. [DSSTATS002B]\n");
                    }
                }
            }

            if (!$dsstats_running) {
                return false;
            }

            $sql = "show tables";
            $result = db_fetch_assoc($sql);
            $tables = array();

            foreach ($result as $index => $arr) {
                foreach ($arr as $t) {
                    $tables[] = $t;
                }
            }

            if (!in_array('data_source_stats_hourly_last', $tables)) {
                wm_debug('ReadData DSStats: data_source_stats_hourly_last database table not found. [DSSTATS003]\n');
                return false;
            }

            return true;
        }

        return false;
    }

    # dsstats:<datatype>:<local_data_id>:<rrd_name_in>:<rrd_name_out>

    // Actually read data from a data source, and return it
    // returns a 3-part array (invalue, outvalue and datavalid time_t)
    // invalue and outvalue should be -1,-1 if there is no valid data
    // data_time is intended to allow more informed graphing in the future
    public function readData($targetstring, &$map, &$item)
    {
        $this->data[IN] = null;
        $this->data[OUT] = null;
        $pdo = weathermap_get_pdo();

        $local_data_id = null;
        $dsnames = array(IN => "traffic_in", OUT => "traffic_out");

        $inbw = null;
        $outbw = null;

        $table = "";
        $keyfield = "rrd_name";
        $datatype = "";
        $field = "";

        if (preg_match('/^dsstats:(\d+):([\-a-zA-Z0-9_]+):([\-a-zA-Z0-9_]+)$/', $targetstring, $matches)) {
            $local_data_id = $matches[1];
            $dsnames[IN] = $matches[2];
            $dsnames[OUT] = $matches[3];

            $datatype = "last";

            if ($map->get_hint("dsstats_default_type") != '') {
                $datatype = $map->get_hint("dsstats_default_type");
                wm_debug("Default datatype changed to " . $datatype . ".\n");
            }
        } elseif (preg_match('/^dsstats:([a-z]+):(\d+):([\-a-zA-Z0-9_]+):([\-a-zA-Z0-9_]+)$/', $targetstring, $matches)) {
            $dsnames[IN] = $matches[3];
            $dsnames[OUT] = $matches[4];
            $datatype = $matches[1];
            $local_data_id = $matches[2];
        }

        if (substr($datatype, 0, 5) == "daily") {
            $table = "data_source_stats_daily";
        }
        if (substr($datatype, 0, 6) == "weekly") {
            $table = "data_source_stats_weekly";
        }
        if (substr($datatype, 0, 7) == "monthly") {
            $table = "data_source_stats_monthly";
        }
        if (substr($datatype, 0, 6) == "hourly") {
            $table = "data_source_stats_hourly";
        }
        if (substr($datatype, 0, 6) == "yearly") {
            $table = "data_source_stats_yearly";
        }

        if (substr($datatype, -7) == "average") {
            $field = "average";
        }
        if (substr($datatype, -4) == "peak") {
            $field = "peak";
        }

        if ($datatype == "last") {
            $field = "calculated";
            $table = "data_source_stats_hourly_last";
        }

        if ($datatype == "wm") {
            $field = "last_calc";
            $table = "weathermap_data";
            $keyfield = "data_source_name";
        }

        if ($table != "" and $field != "") {
            $SQL = sprintf(
                "select %s as name, %s as result from %s where local_data_id=%d and (%s=%s or %s=%s)",
                $keyfield,
                $field,
                $table,
                $local_data_id,
                $keyfield,
                $pdo->quote($dsnames[IN]),
                $keyfield,
                $pdo->quote($dsnames[OUT])
            );

            $results = db_fetch_assoc($SQL);
            if (sizeof($results) > 0) {
                foreach ($results as $result) {
                    foreach (array(IN, OUT) as $dir) {
                        if (($dsnames[$dir] == $result['name']) && ($result['result'] != -90909090909) && ($result['result'] != 'U')) {
                            $this->data[$dir] = $result['result'];
                        }
                    }
                }
            }

            if ($datatype == 'wm' && ($this->data[IN] == null || $this->data[OUT] == null)) {
                wm_debug("Didn't get data for 'wm' source. Inserting new tasks for next poller cycle\n");
                // insert the required details into weathermap_data, so it will be picked up next time
                $SQL = sprintf(
                    "select data_template_data.data_source_path as path from data_template_data,data_template_rrd where data_template_data.local_data_id=data_template_rrd.local_data_id and data_template_rrd.local_data_id=%d",
                    $local_data_id
                );
                $result = db_fetch_row($SQL);
                if (sizeof($result) > 0) {
                    $db_rrdname = $result['path'];
                    wm_debug("Filename is $db_rrdname");
                    foreach (array(IN, OUT) as $dir) {
                        if ($this->data[$dir] === null) {
                            $statement = $pdo->prepare("insert into weathermap_data (rrdfile, data_source_name, sequence, local_data_id) values (?,?,?,?)");
                            $statement->execute(array($db_rrdname, $dsnames[$dir], 0, $local_data_id));
                        }
                    }
                } else {
                    wm_warn("DSStats ReadData: Failed to find a filename for DS id $local_data_id [WMDSTATS01]");
                }
            }
        }

        // fill all that other information (ifSpeed, etc)
        // (but only if it's not switched off!)
        if (($map->get_hint("dsstats_no_cacti_extras") === null) && $local_data_id > 0) {
            updateCactiData($item, $local_data_id);
        }

        return $this->returnData();
    }
}

// vim:ts=4:sw=4:
