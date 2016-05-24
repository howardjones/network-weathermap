<?php
class WeatherMapDataSource_cacti extends WeatherMapDataSource
{
    function Init(&$map)
    {
        if ($map->context === 'cacti') {
            if (true === function_exists('db_fetch_row')) {
                return (true);
            } else {
                wm_debug('ReadData cacti: Cacti database library not found.\n');
            }
        } else {
            wm_debug("ReadData cacti: Can only run from Cacti environment.\n");
        }

        return (false);
    }

    function Recognise($targetstring)
    {
        if (1 === preg_match('/^cacti:(\d+)$/', $targetstring, $matches)) {
            return true;
        } else {
            return false;
        }
    }

    function ReadData($targetstring, &$map, &$item)
    {

        $data[IN] = null;
        $data[OUT] = null;
        $data_time = 0;

        if (1 === preg_match('/^cacti:(\d+)$/', $targetstring, $matches)) {
            $local_data_id = intval($matches[1]);

            $SQL = 'select * from weathermap_data where local_data_id='.$local_data_id;

            $result = db_fetch_row($SQL);

        }      
            
		wm_debug( sprintf("cacti ReadData: Returning (%s, %s, %s)\n",
		        string_or_null($data[IN]),
		        string_or_null($data[OUT]),
		        $data_time
        	));

        return (array (
            $data[IN],
            $data[OUT],
            $data_time
        ));
    }
}


// vim:ts=4:sw=4:
