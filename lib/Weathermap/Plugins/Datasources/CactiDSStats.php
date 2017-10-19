<?php

//include_once dirname(__FILE__) . "/../Utilitymespace Weathermap\Plugins\Datasources;
namespace Weathermap\Plugins\Datasources;

use Weathermap\Core\MapUtility;
use Weathermap\Core\Map;
use Weathermap\Core\MapDataItem;

class CactiDSStats extends Base
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
                MapUtility::debug("ReadData DSStats: Cacti database library not found. [DSSTATS001]\n");
                return false;
            }

            $dsstatsRunning = false;

            MapUtility::debug("ReadData DSStats: Checking for 1.x integrated...\n");
            if (function_exists("read_config_option")) {
                if (read_config_option("dsstats_enable") == "on") {
                    $dsstatsRunning = true;
                    MapUtility::debug("ReadData DSStats: Found 1.x integrated DSStats\n");
                } else {
                    MapUtility::debug("ReadData DSStats: No 1.x integrated DSStats\n");
                }
            }

            if (!$dsstatsRunning) {
                MapUtility::debug("ReadData DSStats: Checking for 0.8.8 plugin...\n");
                if (function_exists("api_plugin_is_enabled")) {
                    if (api_plugin_is_enabled('dsstats')) {
                        MapUtility::debug("ReadData DSStats: DSStats plugin enabled. [DSSTATS002B]\n");
                        $dsstatsRunning = true;
                    } else {
                        MapUtility::debug("ReadData DSStats: DSStats plugin NOT enabled. [DSSTATS002B]\n");
                    }
                }
            }

            if (!$dsstatsRunning) {
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
                MapUtility::debug('ReadData DSStats: data_source_stats_hourly_last database table not found. [DSSTATS003]\n');
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


    /**
     * @param string $targetstring
     * @param Map $map
     * @param MapDataItem $item
     * @return array
     */
    public function readData($targetstring, &$map, &$item)
    {
        $this->data[IN] = null;
        $this->data[OUT] = null;
        $pdo = weathermap_get_pdo();

        $localDataId = null;
        $dsNames = array(IN => "traffic_in", OUT => "traffic_out");

        $inBandwidth = null;
        $outBandwidth = null;

        $table = "";
        $keyfield = "rrd_name";
        $datatype = "";
        $field = "";

        if (preg_match('/^dsstats:(\d+):([\-a-zA-Z0-9_]+):([\-a-zA-Z0-9_]+)$/', $targetstring, $matches)) {
            $localDataId = $matches[1];
            $dsNames[IN] = $matches[2];
            $dsNames[OUT] = $matches[3];

            $datatype = "last";

            if ($map->getHint("dsstats_default_type") != '') {
                $datatype = $map->getHint("dsstats_default_type");
                MapUtility::debug("Default datatype changed to " . $datatype . ".\n");
            }
        } elseif (preg_match('/^dsstats:([a-z]+):(\d+):([\-a-zA-Z0-9_]+):([\-a-zA-Z0-9_]+)$/', $targetstring, $matches)) {
            $dsNames[IN] = $matches[3];
            $dsNames[OUT] = $matches[4];
            $datatype = $matches[1];
            $localDataId = $matches[2];
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
                $localDataId,
                $keyfield,
                $pdo->quote($dsNames[IN]),
                $keyfield,
                $pdo->quote($dsNames[OUT])
            );

            $results = \db_fetch_assoc($SQL);
            if (count($results) > 0) {
                foreach ($results as $result) {
                    foreach (array(IN, OUT) as $dir) {
                        if (($dsNames[$dir] == $result['name']) && ($result['result'] != -90909090909) && ($result['result'] != 'U')) {
                            $this->data[$dir] = $result['result'];
                        }
                    }
                }
            }

            if ($datatype == 'wm' && ($this->data[IN] == null || $this->data[OUT] == null)) {
                MapUtility::debug("Didn't get data for 'wm' source. Inserting new tasks for next poller cycle\n");
                // insert the required details into weathermap_data, so it will be picked up next time
                $SQL = sprintf(
                    "select data_template_data.data_source_path as path from data_template_data,data_template_rrd where data_template_data.local_data_id=data_template_rrd.local_data_id and data_template_rrd.local_data_id=%d",
                    $localDataId
                );
                $result = \db_fetch_row($SQL);
                if (count($result) > 0) {
                    $databaseRRDName = $result['path'];
                    MapUtility::debug("Filename is $databaseRRDName");
                    foreach (array(IN, OUT) as $dir) {
                        if ($this->data[$dir] === null) {
                            $statement = $pdo->prepare("insert into weathermap_data (rrdfile, data_source_name, sequence, local_data_id) values (?,?,?,?)");
                            $statement->execute(array($databaseRRDName, $dsNames[$dir], 0, $localDataId));
                        }
                    }
                } else {
                    MapUtility::warn("DSStats ReadData: Failed to find a filename for DS id $localDataId [WMDSTATS01]");
                }
            }
        }

        // fill all that other information (ifSpeed, etc)
        // (but only if it's not switched off!)
        if (($map->getHint("dsstats_no_cacti_extras") === null) && $localDataId > 0) {
            updateCactiData($item, $localDataId);
        }

        return $this->returnData();
    }
}

// vim:ts=4:sw=4:
