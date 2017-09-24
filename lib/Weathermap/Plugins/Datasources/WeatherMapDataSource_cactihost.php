<?php

//require_once dirname(__FILE__) . "/../database.php";

namespace Weathermap\Plugins\Datasources;

use Weathermap\Core\MapUtility;

class WeatherMapDataSource_cactihost extends DatasourceBase
{
    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array(
            '/^cactihost:(\d+)$/'
        );

        $this->name= "CactiHost";
    }

    public function init(&$map)
    {
        if ($map->context == 'cacti') {
            if (function_exists('db_fetch_row')) {
                return true;
            } else {
                MapUtility::wm_debug('ReadData CactiHost: Cacti database library not found.\n');
            }
        } else {
            MapUtility::wm_debug("ReadData CactiHost: Can only run from Cacti environment.\n");
        }

        return false;
    }

    /**
     * @param string $targetstring
     * @param WeatherMap $map
     * @param WeatherMapDataItem $item
     * @return array
     */
    public function readData($targetstring, &$map, &$item)
    {
        $this->data[IN] = null;
        $this->data[OUT] = null;

        if (preg_match($this->regexpsHandled[0], $targetstring, $matches)) {
            $cacti_id = intval($matches[1]);

            $pdo = weathermap_get_pdo();

            $statement=$pdo->prepare("select * from host where id=?");

            // 0=disabled
            // 1=down
            // 2=recovering
            // 3=up

            $statement->execute(array($cacti_id));

            $state = -1;
            $result = $statement->fetch(PDO::FETCH_ASSOC);
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
                $this->data[OUT] = $state;
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
        }
        return $this->returnData();
    }
}


// vim:ts=4:sw=4:
