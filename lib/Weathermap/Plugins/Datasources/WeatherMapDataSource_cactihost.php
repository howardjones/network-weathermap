<?php

//require_once dirname(__FILE__) . "/../database.php";

namespace Weathermap\Plugins\Datasources;

use Weathermap\Core\MapUtility;
use Weathermap\Core\Map;
use Weathermap\Core\MapDataItem;
use PDO;

class WeatherMapDataSource_cactihost extends DatasourceBase
{
    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array(
            '/^cactihost:(\d+)$/'
        );

        $this->name = "CactiHost";
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
     * @param Map $map
     * @param MapDataItem $item
     * @return array
     */
    public function readData($targetstring, &$map, &$item)
    {
        $this->data[IN] = null;
        $this->data[OUT] = null;

        if (preg_match($this->regexpsHandled[0], $targetstring, $matches)) {
            $cactiHostId = intval($matches[1]);

            $pdo = weathermap_get_pdo();

            $statement = $pdo->prepare("select * from host where id=?");

            // 0=disabled
            // 1=down
            // 2=recovering
            // 3=up

            $statement->execute(array($cactiHostId));

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
                $item->addNote("state", $statename);
                $item->addNote("cacti_description", $result['description']);

                $item->addNote("cacti_hostname", $result['hostname']);
                $item->addNote("cacti_curtime", $result['cur_time']);
                $item->addNote("cacti_avgtime", $result['avg_time']);
                $item->addNote("cacti_mintime", $result['min_time']);
                $item->addNote("cacti_maxtime", $result['max_time']);
                $item->addNote("cacti_availability", $result['availability']);

                $item->addNote("cacti_faildate", $result['status_fail_date']);
                $item->addNote("cacti_recdate", $result['status_rec_date']);
            }
        }
        return $this->returnData();
    }
}


// vim:ts=4:sw=4:
