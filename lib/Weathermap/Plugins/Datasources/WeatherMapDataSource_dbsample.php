<?php
// Sample Pluggable datasource for PHP Weathermap 0.9
// - read a pair of values from a database, and return it

// TARGET dbplug:databasename:username:pass:hostkey

namespace Weathermap\Plugins\Datasources;

use Weathermap\Core\MapUtility;
use Weathermap\Core\Map;
use Weathermap\Core\MapDataItem;
use PDO;
use PDOException;

class WeatherMapDataSource_dbsample extends DatasourceBase
{

    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array(
            '/^dbplug:([^:]+)$/'
        );
        $this->name = "dbsample";
    }

    public function init(&$map)
    {
        if (!function_exists("mysql_connect")) {
            return false;
        }

        return (true);
    }

    /**
     * @param string $targetstring
     * @param Map $map
     * @param MapDataItem $item
     * @return array
     */
    public function readData($targetstring, &$map, &$item)
    {
        $pdo = null;
        $this->data[IN] = null;
        $this->data[OUT] = null;


        if (preg_match('/^dbplug:([^:]+)$/', $targetstring, $matches)) {
            $database_user = $map->getHint('dbplug_dbuser');
            $database_pass = $map->getHint('dbplug_dbpass');
            $database_name = $map->getHint('dbplug_dbname');
            $database_host = $map->getHint('dbplug_dbhost');


            try {
                # MySQL with PDO_MYSQL
                $pdo = new PDO("mysql:host=$database_host;dbname=$database_name", $database_user, $database_pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                MapUtility::wm_warn($e->getMessage());
            }

            if ($pdo) {
                $statement = $pdo->prepare("SELECT infield, outfield FROM tablename WHERE host=? LIMIT 1");
                $result = $statement->execute(array($matches[1]));

                if (!$result) {
                    MapUtility::wm_warn("dbsample ReadData: Invalid query: " . $pdo->errorCode() . "\n");
                } else {
                    $row = $statement->fetch(PDO::FETCH_ASSOC);
                    $this->data[IN] = $row['infield'];
                    $this->data[OUT] = $row['outfield'];
                }

                $this->dataTime = time();
            }
        }

        return $this->returnData();
    }
}

// vim:ts=4:sw=4:
