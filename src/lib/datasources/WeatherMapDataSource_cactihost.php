<?php

class WeatherMapDataSource_cactihost extends WeatherMapDataSource
{

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

    function Recognise($targetString)
    {
        if (preg_match("/^cactihost:(\d+)$/", $targetString)) {
            return true;
        } else {
            return false;
        }
    }

    function ReadData($targetString, &$map, &$mapItem)
    {

        $data[IN] = null;
        $data[OUT] = null;
        $data_time = 0;

        $state_map = array(
            5 => "unknown",
            3 => "up",
            2 => "recovering",
            1 => "down",
            0 => "disabled"
        );

        $result_map = array(
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

        if (preg_match('/^cactihost:(\d+)$/', $targetString, $matches)) {
            $cacti_id = intval($matches[1]);

            $SQL = "select * from host where id=$cacti_id";
            // 0=disabled
            // 1=down
            // 2=recovering
            // 3=up
            // (4 is tholdbreached in cactimonitor, so skipped here for compatibility)
            // 5=unknown

            $result = db_fetch_row($SQL);
            if (isset($result)) {
                // create a note, which can be used in icon filenames or labels more nicely

                $state = $result['status'];

                if ($result['disabled']) {
                    $state = 0;
                }

                $statename = $state_map[$result['status']];


                $data[IN] = $state;
                $data[OUT] = $state;
                $mapItem->add_note("state", $statename);

                foreach ($result_map as $name=>$result_name) {
                    $mapItem->add_note($name, $result[$result_name]);
                }
            }
        }

        wm_debug("CactiHost ReadData: Returning (" . ($data[IN] === null ? 'null' : $data[IN]) . "," . ($data[OUT] === null ? 'null' : $data[OUT]) . ",$data_time)\n");

        return (array($data[IN], $data[OUT], $data_time));
    }
}


// vim:ts=4:sw=4:
