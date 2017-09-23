<?php

// TODO - This seems to be half-written
// * Doesn't do anything with results of query
// * Doesn't add any rows to weathermap_data, so nothing would be collected anyway


class WeatherMapDataSource_cacti extends WeatherMapDataSource
{
    protected $name = "Cacti";

    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array(
            '/^cacti:(\d+)$/'
        );
    }


    public function Init(&$map)
    {
        if ($map->context === 'cacti') {
            if (true === function_exists('db_fetch_row')) {
                return true;
            } else {
                wm_debug('ReadData cacti: Cacti database library not found.\n');
            }
        } else {
            wm_debug("ReadData cacti: Can only run from Cacti environment.\n");
        }

        return false;
    }

    public function ReadData($targetstring, &$map, &$item)
    {
        $this->data[IN] = null;
        $this->data[OUT] = null;

        if (1 === preg_match($this->regexpsHandled[0], $targetstring, $matches)) {
            $local_data_id = intval($matches[1]);

            // This is very unlikely to work - nothing is ADDING the correct lines to weathermap_data!
            $SQL = 'select * from weathermap_data where local_data_id='.$local_data_id;

            $result = db_fetch_row($SQL);
        }

        return $this->ReturnData();
    }
}


// vim:ts=4:sw=4:
