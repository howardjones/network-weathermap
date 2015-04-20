<?php

class WeatherMapDataSource_cactihost extends WeatherMapDataSource
{

    function Init(&$map)
    {
        if ($map->context == 'cacti') {
            if (function_exists('db_fetch_row')) {
                return(true);
            } else {
                wm_debug('ReadData CactiHost: Cacti database library not found.\n');
            }
        } else {
            wm_debug("ReadData CactiHost: Can only run from Cacti environment.\n");
        }

        return(false);
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

        if (preg_match("/^cactihost:(\d+)$/", $targetString, $matches)) {
            $cacti_id = intval($matches[1]);

            $SQL = "select * from host where id=$cacti_id";
            // 0=disabled
            // 1=down
            // 2=recovering
            // 3=up
            // (4 is tholdbreached in cactimonitor, so skipped here for compatibility)
            // 5=unknown

            $state = -1;
            $result = db_fetch_row($SQL);
            if (isset($result)) {
                // create a note, which can be used in icon filenames or labels more nicely
                if ($result['status'] == 5) {
                    $state = 5;
                    $statename = 'unknown';
                }

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

                $data[IN] = $state;
                $data[OUT] = $state;
                $mapItem->add_note("state", $statename);
                $mapItem->add_note("cacti_description", $result['description']);

                $mapItem->add_note("cacti_hostname", $result['hostname']);
                $mapItem->add_note("cacti_curtime", $result['cur_time']);
                $mapItem->add_note("cacti_avgtime", $result['avg_time']);
                $mapItem->add_note("cacti_mintime", $result['min_time']);
                $mapItem->add_note("cacti_maxtime", $result['max_time']);
                $mapItem->add_note("cacti_availability", $result['availability']);

                $mapItem->add_note("cacti_faildate", $result['status_fail_date']);
                $mapItem->add_note("cacti_recdate", $result['status_rec_date']);
            }
        }

        wm_debug("CactiHost ReadData: Returning (".($data[IN]===null?'null':$data[IN]).",".($data[OUT]===null?'null':$data[OUT]).",$data_time)\n");

        return( array($data[IN], $data[OUT], $data_time) );
    }
}


// vim:ts=4:sw=4:
