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

    private $thold10;

    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array(
            '/^cacti(thold|monitor):(\d+)$/',
            '/^cactithold:(\d+):(\d+)$/'
        );
        $this->name = "CactiTHold";
        $this->thold10 = false;
    }

    function Init(&$map)
    {
        global $plugins;


        if ($map->context == 'cacti') {
            $pdo = weathermap_get_pdo();

            if (!function_exists('db_fetch_row')) {
                wm_debug("ReadData CactiTHold: Cacti database library not found. [THOLD001]\n");
                return (FALSE);
            }

            $thold_present = false;

            if (function_exists("api_plugin_is_enabled")) {
                if (api_plugin_is_enabled('thold')) {
                    $thold_present = true;
                }
            }

            if (!$thold_present) {
                wm_debug("ReadData CactiTHold: THold plugin not enabled. [THOLD002]\n");
            }

            $statement = $pdo->prepare("show tables");
            $statement->execute();
            $result = $statement->fetchall(PDO::FETCH_ASSOC);

            foreach ($result as $index => $arr) {
                foreach ($arr as $t) {
                    $tables[] = $t;
                }
            }

            if (!in_array('thold_data', $tables)) {
                wm_debug('ReadData CactiTHold: thold_data database table not found. [THOLD003]\n');
                return false;
            }

            // TODO: Check for thold >1.0 or earlier (field names changed)
            // and update $this->thold10 appropriately

            $statement = $pdo->prepare("select * from plugin_config where directory='thold'");
            $statement->execute();
            $result = $statement->fetchall(PDO::FETCH_ASSOC);
            if(sizeof($result)==1) {
                $version = $result[0]['version'];
                if(substr($version,0,1) != "0") {
                    $this->thold10 = true;
                    wm_debug("ReadData CactiTHold: detected Thold > 1.0, will adjust field names\n");
                } else {
                    wm_debug("ReadData CactiTHold: detected Thold < 1.0, using classic field names\n");
                }
            }

            return true;
        } else {
            wm_debug("ReadData CactiTHold: Can only run from Cacti environment. [THOLD004]\n");
        }

        return false;
    }

    /**
     * @param string $targetstring The string from the config file
     * @param WeatherMap $map A reference to the map object (redundant)
     * @param WeatherMapDataItem $item A reference to the object this target is attached to
     * @return array invalue, outvalue, unix timestamp that the data was valid
     */
    function ReadData($targetstring, &$map, &$item)
    {
        $this->dataTime = time();

        $pdo = weathermap_get_pdo();

        if (preg_match('/^cactithold:(\d+):(\d+)$/', $targetstring, $matches)) {
            // Returns 0 if threshold is not breached, 1 if it is.
            // use target aggregation to build these up into a 'badness' percentage
            // takes the same two values that are visible in thold's own URLs (the actual thold ID isn't shown anywhere)

            $rra_id = intval($matches[1]);
            $data_id = intval($matches[2]);

//            $SQL2 = "select thold_alert from thold_data where rra_id=$rra_id and data_id=$data_id and thold_enabled='on'";

            $statement = $pdo->prepare("select thold_alert from thold_data where rra_id=? and data_id=? and thold_enabled='on'");
            $statement->execute(array($rra_id, $data_id));
            $result = $statement->fetch(PDO::FETCH_ASSOC);

//            $result = db_fetch_row($SQL2);
            if (isset($result)) {
                if ($result['thold_alert'] > 0) {
                    $this->data[IN] = 1;
                } else {
                    $this->data[IN] = 0;
                }
                $this->data[OUT] = 0;
            }
        } elseif (preg_match('/^cacti(thold|monitor):(\d+)$/', $targetstring, $matches)) {
            $type = $matches[1];
            $id = intval($matches[2]);

            if ($type == 'thold') {
                // VERY simple. Returns 0 if threshold is not breached, 1 if it is.
                // use target aggregation to build these up into a 'badness' percentage
//                $SQL2 = "select thold_alert from thold_data where id=$id and thold_enabled='on'";
                $statement = $pdo->prepare("select thold_alert from thold_data where id=? and thold_enabled='on'");
                $statement->execute(array($id));
                $result = $statement->fetch(PDO::FETCH_ASSOC);

//                $result = db_fetch_row($SQL2);
                if (isset($result)) {
                    if ($result['thold_alert'] > 0) {
                        $this->data[IN] = 1;
                    } else {
                        $this->data[IN] = 0;
                    }
                    $this->data[OUT] = 0;
                }
            }

            if ($type == 'monitor') {
                wm_debug("CactiTHold ReadData: Getting cacti basic state for host $id\n");
//                $SQL = "select * from host where id=$id";

                $statement = $pdo->prepare("select * from host where id=?");
                $statement->execute(array($id));
                $result = $statement->fetch(PDO::FETCH_ASSOC);

                // 0=disabled
                // 1=down
                // 2=recovering
                // 3=up
                // 4=tholdbreached

                $state = -1;
                $statename = '';
//                $result = db_fetch_row($SQL);
                if (isset($result)) {
                    // create a note, which can be used in icon filenames or labels more nicely
                    if ($result['status'] == 1) {
                        $state = 1;
                        $statename = 'down';
                    }
                    if ($result['status'] == 2) {
                        $state = 2;
                        $statename = 'recovering';
                    }
                    if ($result['status'] == 3) {
                        $state = 3;
                        $statename = 'up';
                    }
                    if ($result['disabled']) {
                        $state = 0;
                        $statename = 'disabled';
                    }

                    $this->data[IN] = $state;
                    $this->data[OUT] = 0;
                    $item->add_note("state", $statename);
                    $item->add_note("cacti_description", $result['description']);

                    $item->add_note("cacti_hostname", $result['hostname']);
                    $item->add_note("cacti_curtime", $result['cur_time']);
                    $item->add_note("cacti_avgtime", $result['avg_time']);
                    $item->add_note("cacti_mintime", $result['min_time']);
                    $item->add_note("cacti_maxtime", $result['max_time']);
                    $item->add_note("cacti_availability", $result['availability']);

                    $item->add_note("cacti_faildate", $result['status_fail_date']);
                    $item->add_note("cacti_recdate", $result['status_rec_date']);
                }
                wm_debug("CactiTHold ReadData: Basic state for host $id is $state/$statename\n");

                wm_debug("CactiTHold ReadData: Checking threshold states for host $id\n");
                $numthresh = 0;
                $numfailing = 0;
                // $SQL2 = "select rra_id, data_id, thold_alert from thold_data,data_local where thold_data.rra_id=data_local.id and data_local.host_id=$id and thold_enabled='on'";

                $statement = $pdo->prepare("select rra_id, data_id, thold_alert from thold_data,data_local where thold_data.rra_id=data_local.id and data_local.host_id=? and thold_enabled='on'");

                if ($this->thold10) {
                    $statement = $pdo->prepare("select local_data_id as rra_id, data_template_rrd_id as data_id, thold_alert from thold_data,data_local where thold_data.local_data_id=data_local.id and data_local.host_id=? and thold_enabled='on'");
                }

                $statement->execute(array($id));
                $queryrows = $statement->fetchAll(PDO::FETCH_ASSOC);

                # $result = db_fetch_row($SQL2);
                // $queryrows = db_fetch_assoc($SQL2);
                if (is_array($queryrows)) {
                    foreach ($queryrows as $th) {
                        $desc = $th['rra_id'] . "/" . $th['data_id'];
                        $v = $th['thold_alert'];
                        $numthresh++;
                        if (intval($th['thold_alert']) > 0) {
                            wm_debug("CactiTHold ReadData: Seen threshold $desc failing ($v)for host $id\n");
                            $numfailing++;
                        } else {
                            wm_debug("CactiTHold ReadData: Seen threshold $desc OK ($v) for host $id\n");
                        }
                    }
                } else {
                    wm_debug("CactiTHold ReadData: Failed to get thold info for host $id\n");
                }

                wm_debug("CactiTHold ReadData: Checked $numthresh and found $numfailing failing\n");

                if (($numfailing > 0) && ($numthresh > 0) && ($state == 3)) {
                    $state = 4;
                    $statename = "tholdbreached";
                    $item->add_note("state", $statename);
                    $item->add_note("thold_failcount", $numfailing);
                    $item->add_note("thold_failpercent", ($numfailing / $numthresh) * 100);
                    $this->data[IN] = $state;
                    $this->data[OUT] = $numfailing;
                    wm_debug("CactiTHold ReadData: State is $state/$statename\n");
                } elseif ($numthresh > 0) {
                    $item->add_note("thold_failcount", 0);
                    $item->add_note("thold_failpercent", 0);
                    wm_debug("CactiTHold ReadData: Leaving state as $state\n");
                }
            }
        }

        return $this->ReturnData();
    }
}


// vim:ts=4:sw=4:
