<?php
class WeatherMapDataSource_cacti extends WeatherMapDataSource
{
    function Init(&$map)
    {
        if ($map->context == 'cacti') {
            if (function_exists('db_fetch_row')) {
                return (TRUE);
            } else {
                debug('ReadData cacti: Cacti database library not found.\n');
            }
        } else {
            debug("ReadData cacti: Can only run from Cacti environment.\n");
        }

        return (FALSE);
    }

    function Recognise($targetstring)
    {
        if (preg_match("/^cacti:(\d+)$/", $targetstring, $matches)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    function ReadData($targetstring, &$map, &$item)
    {

        $data[IN] = NULL;
        $data[OUT] = NULL;
        $data_time = 0;

        if (preg_match("/^cacti:(\d+)$/", $targetstring, $matches)) {
            $local_data_id = intval($matches[1]);

            $SQL = "select * from weathermap_data where local_data_id=$local_data_id";

            $result = db_fetch_row($SQL);

            if (isset($result)) {

// $SQL_vars = sprintf("select * from host_snmp_cache where host_id=%d and snmp_query_id=%d and snmp_index=%d");
// $r2 = db_fetch_row($SQL_vars);

// $item->add_note("cacti_hostname",$result['hostname']);
            }
            else {
            // no data found, time to add a new row to weathermap_data
            // (but let's validate the local_data_id first)
            }
        }

        debug("cacti ReadData: Returning (" . ($data[IN] === NULL ? 'NULL' : $data[IN])
            . "," . ($data[OUT] === NULL ? 'NULL' : $data[OUT]) . ",$data_time)\n");

        return (array (
            $data[IN],
            $data[OUT],
            $data_time
        ));
    }
}


// vim:ts=4:sw=4:
?>