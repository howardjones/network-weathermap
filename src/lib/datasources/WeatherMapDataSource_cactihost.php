<?php

class WeatherMapDataSource_cactihost extends WeatherMapDataSource
{

    // (4 is tholdbreached in cactimonitor, so skipped here for compatibility)
    private static $state_map = array(
        5 => "unknown",
        3 => "up",
        2 => "recovering",
        1 => "down",
        0 => "disabled"
    );

    private static $result_map = array(
        "cacti_description" => "description",
        "cacti_hostname" => "hostname",
        "cacti_curtime" => "cur_time",
        "cacti_avgtime" => "avg_time",
        "cacti_mintime" => "min_time",
        "cacti_maxtime" => "max_time",
        "cacti_availability" => "availability",
        "cacti_faildate" => "status_fail_date",
        "cacti_recdate" => "status_rec_date"
    );

    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array(
            '/^cactihost:(\d+)$/'
        );

    }

    function Init(&$map)
    {
        if ($map->context == 'cacti') {
            if (function_exists('db_fetch_row')) {
                return (true);
            } else {
                wm_debug('ReadData CactiHost: Cacti database library not found.\n');
            }
        } else {
            wm_debug("ReadData CactiHost: Can only run from Cacti environment.\n");
        }

        return (false);
    }

    function ReadData($targetString, &$map, &$mapItem)
    {

        $data[IN] = null;
        $data[OUT] = null;
        $data_time = 0;

        if (preg_match('/^cactihost:(\d+)$/', $targetString, $matches)) {
            $cacti_id = intval($matches[1]);

            $data = $this->readDataFromCacti($mapItem, $cacti_id, $data);
        }

        wm_debug("CactiHost ReadData: Returning (" . ($data[IN] === null ? 'null' : $data[IN]) . "," . ($data[OUT] === null ? 'null' : $data[OUT]) . ",$data_time)\n");

        return (array($data[IN], $data[OUT], $data_time));
    }

    /**
     * @param $mapItem
     * @param $cacti_id
     * @param $data
     * @return mixed
     */
    private function readDataFromCacti(&$mapItem, $cacti_id, $data)
    {
        $SQL = "select * from host where id=$cacti_id";

        $result = db_fetch_row($SQL);
        if (isset($result)) {
            // create a note, which can be used in icon filenames or labels more nicely

            $state = $result['status'];

            if ($result['disabled']) {
                $state = 0;
            }

            $statename = self::$state_map[$result['status']];


            $data[IN] = $state;
            $data[OUT] = $state;
            $mapItem->add_note("state", $statename);

            foreach (self::$result_map as $name => $result_name) {
                $mapItem->add_note($name, $result[$result_name]);
            }
            return $data;
        }
        return $data;
    }
}


// vim:ts=4:sw=4:
