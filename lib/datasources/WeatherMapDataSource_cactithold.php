<?php

// Cacti thold/monitor DS plugin
//   Can read state of Thresholds from the THold Cacti plugin
//   and also overall host state, in the style of the Monitor plugin (it doesn't depend on that plugin to do this)
//
// It DOES depend on THold though, obviously!
//
// Possible TARGETs:
//
//  cactithold:234
//  (internal thold id - returns 0 for OK, and 1 for breach)
//
//  cactithold:12:444
//  (the two IDs seen in thold URLs- also returns 0 for OK, and 1 for breach)
//
//  cactimonitor:22
//  (cacti hostid - returns host state (0-3) or 4 for failing some thresholds)
//  also sets all the same variables as cactihost: would, and a new possible 'state' name of 'tholdbreached'
//
// Original development for this plugin was paid for by
//    Stellar Consulting

class WeatherMapDataSource_cactithold extends WeatherMapDataSource
{
    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array(
            '/^cactithold:(\d+)$/',
            '/^cactimonitor:(\d+)$/',
            '/^cactithold:(\d+):(\d+)$/'
        );
    }

    public function Init(&$map)
    {

        if ($map->context == 'cacti') {
            if (!function_exists('db_fetch_row')) {
                wm_debug("ReadData CactiTHold: Cacti database library not found. [THOLD001]\n");
                return(false);
            }

            if (!$this->checkForTholdPlugin()) {
                wm_debug("ReadData CactiTHold: THold plugin not enabled. [THOLD002]\n");
                return false;
            }

            return $this->checkForTholdTables();
        }

        wm_debug("ReadData CactiTHold: Can only run from Cacti environment. [THOLD004]\n");

        return(false);
    }

    public function ReadData($targetString, &$map, &$mapItem)
    {
        $data[IN] = null;
        $data[OUT] = null;
        $data_time = 0;

        if (preg_match($this->regexpsHandled[0], $targetString, $matches)) {
            list($data) = $this->readDataByTholdID($matches[1], $data);
        }

        if (preg_match($this->regexpsHandled[1], $targetString, $matches)) {
            $data = $this->readDataHostState($mapItem, $matches[1], $data);
        }

        if (preg_match($this->regexpsHandled[2], $targetString, $matches)) {
            $data = $this->readDataByRraID($matches[1], $matches[2], $data);
        }

        wm_debug("CactiTHold ReadData: Returning (".($data[IN]===null?'null':$data[IN]).",".($data[OUT]===null?'null':$data[OUT]).", $data_time)\n");

        return(array($data[IN], $data[OUT], $data_time));
    }

    /**
     * @param $rraID
     * @param $dataID
     * @param $data
     * @return mixed
     * @internal param $matches
     */
    private function readDataByRraID($rraID, $dataID, $data)
    {
        // Returns 0 if threshold is not breached, 1 if it is.
        // use target aggregation to build these up into a 'badness' percentage
        // takes the same two values that are visible in thold's own URLs (the actual thold ID isn't shown anywhere)

        $SQL = sprintf("select thold_alert from thold_data where rra_id=%d and data_id=%d and thold_enabled='on'", $rraID, $dataID);
        $tholdInfo = db_fetch_row($SQL);
        $data = $this->interpretTholdInfo($data, $tholdInfo);

        return $data;
    }

    /**
     * @param $id
     * @param $data
     * @return array
     */
    private function readDataByTholdID($id, $data)
    {
        // VERY simple. Returns 0 if threshold is not breached, 1 if it is.
        // use target aggregation to build these up into a 'badness' percentage
        $SQL = sprintf("select thold_alert from thold_data where id=%d and thold_enabled='on'", $id);
        $tholdInfo = db_fetch_row($SQL);
        $data = $this->interpretTholdInfo($data, $tholdInfo);

        return $data;
    }

    /**
     * @param $mapItem
     * @param $id
     * @param $data
     * @return mixed
     */
    private function readDataHostState(&$mapItem, $id, $data)
    {
        wm_debug("CactiTHold ReadData: Getting cacti basic state for host $id\n");

        $states = array(
            -1 => "pluginfailed",
            0 => "disabled",
            1 => "down",
            2 => "recovering",
            3 => "up",
            4 => "tholdbreached",
            5 => "unknown"
        );

        $state = -1;
        $stateName = '';

        $SQL = "select * from host where id=$id";
        $hostInfo = db_fetch_row($SQL);

        if (isset($hostInfo)) {
            // create a note, which can be used in icon filenames or labels more nicely
            if ($hostInfo['status'] == 1 || $hostInfo['status'] == 2 || $hostInfo['status'] == 3 || $hostInfo['status'] == 5) {
                $state = $hostInfo['status'];
            }

            if ($hostInfo['disabled']) {
                $state = 0;
            }
            $stateName = $states[$state];

            $this->recordAdditionalNotes($mapItem, $hostInfo);
        }
        wm_debug("CactiTHold ReadData: Basic state for host $id is $state/$stateName\n");

        wm_debug("CactiTHold ReadData: Checking threshold states for host $id\n");
        list($thresholdCount, $failingCount) = $this->readDataFailingTholdCount($id);

        $state = $this->checkForBreachState($mapItem, $failingCount, $thresholdCount, $state);

        $mapItem->add_note("state", $states[$state]);
        $data[IN] = $state;
        $data[OUT] = $failingCount;

        return $data;
    }

    /**
     * @param $hostID
     * @return array
     */
    private function readDataFailingTholdCount($hostID)
    {
        $thresholdCount = 0;
        $failingCount = 0;

        $SQL = "select rra_id, data_id, thold_alert from thold_data,data_local where thold_data.rra_id=data_local.id and data_local.host_id=$hostID and thold_enabled='on'";
        $hostThresholds = db_fetch_assoc($SQL);

        if (!is_array($hostThresholds)) {
            wm_debug("CactiTHold ReadData: Failed to get thold info for host $hostID\n");
            return array(0, 0);
        }

        foreach ($hostThresholds as $threshold) {
            $description = $threshold['rra_id'] . "/" . $threshold['data_id'];
            $value = $threshold['thold_alert'];
            $thresholdCount++;
            if (intval($threshold['thold_alert']) > 0) {
                wm_debug("CactiTHold ReadData: Seen threshold $description failing ($value)for host $hostID\n");
                $failingCount++;
            } else {
                wm_debug("CactiTHold ReadData: Seen threshold $description OK ($value) for host $hostID\n");
            }
        }

        wm_debug("CactiTHold ReadData: Checked $thresholdCount and found $failingCount failing for host $hostID\n");
        return array($thresholdCount, $failingCount);
    }

    /**
     * @param $mapItem
     * @param $failingCount
     * @param $thresholdCount
     * @param $state
     * @param $states
     * @return array
     */
    private function checkForBreachState(&$mapItem, $failingCount, $thresholdCount, $state)
    {
        if (($failingCount > 0) && ($thresholdCount > 0) && ($state == 3)) {
            $state = 4;
            $mapItem->add_note("thold_failcount", $failingCount);
            $mapItem->add_note("thold_failpercent", ($failingCount / $thresholdCount) * 100);

            wm_debug("CactiTHold ReadData: State is $state\n");
        } elseif ($thresholdCount > 0) {
            $mapItem->add_note("thold_failcount", 0);
            $mapItem->add_note("thold_failpercent", 0);
            wm_debug("CactiTHold ReadData: Leaving state as $state\n");
        }
        return $state;
    }

    /**
     * @return bool
     * @internal param $plugins
     */
    private function checkForTholdPlugin()
    {
        global $plugins;

        $thold_present = false;

        if (function_exists("api_plugin_is_enabled")) {
            if (api_plugin_is_enabled('thold')) {
                $thold_present = true;
            }
        }

        if (isset($plugins) && in_array('thold', $plugins)) {
            $thold_present = true;
        }

        return $thold_present;
    }

    private function checkForTholdTables()
    {
        $sql = "show tables";
        $result = db_fetch_assoc($sql);
        if (null === $result || !is_array($result) || count($result)==0) {
            throw new Exception(mysql_error());
        }

        $tables = array();

        foreach ($result as $arr) {
            foreach ($arr as $t) {
                $tables[] = $t;
            }
        }

        if (!in_array('thold_data', $tables)) {
            wm_debug('ReadData CactiTHold: thold_data database table not found. [THOLD003]\n');
            return false;
        }

        return true;
    }

    /**
     * @param $data
     * @param $tholdInfo
     * @return mixed
     */
    private function interpretTholdInfo($data, $tholdInfo)
    {
        if (isset($tholdInfo)) {
            if ($tholdInfo['thold_alert'] > 0) {
                $data[IN] = 1;
            } else {
                $data[IN] = 0;
            }
            $data[OUT] = 0;
            return $data;
        }
        return $data;
    }

    /**
     * @param $mapItem
     * @param $hostInfo
     */
    private function recordAdditionalNotes(&$mapItem, $hostInfo)
    {
        $mapItem->add_note("cacti_description", $hostInfo['description']);

        $mapItem->add_note("cacti_hostname", $hostInfo['hostname']);
        $mapItem->add_note("cacti_curtime", $hostInfo['cur_time']);
        $mapItem->add_note("cacti_avgtime", $hostInfo['avg_time']);
        $mapItem->add_note("cacti_mintime", $hostInfo['min_time']);
        $mapItem->add_note("cacti_maxtime", $hostInfo['max_time']);
        $mapItem->add_note("cacti_availability", $hostInfo['availability']);

        $mapItem->add_note("cacti_faildate", $hostInfo['status_fail_date']);
        $mapItem->add_note("cacti_recdate", $hostInfo['status_rec_date']);
    }
}

// vim:ts=4:sw=4:
