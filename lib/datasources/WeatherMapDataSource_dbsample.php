<?php
// Sample Pluggable datasource for PHP Weathermap 0.9
// - read a pair of values from a database, and return it

// TARGET dbplug:databasename:username:pass:hostkey

class WeatherMapDataSource_dbsample extends WeatherMapDataSource
{

    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array(
            '/^dbplug:([^:]+)$/'
        );
        $this->name = "dbsample";
    }

    public function Init(&$map)
    {
        if (!function_exists("mysql_connect")) {
            return false;
        }

        return (true);
    }


    public function ReadData($targetstring, &$map, &$item)
    {
        $pdo = null;


        if (preg_match('/^dbplug:([^:]+)$/', $targetstring, $matches)) {
            $database_user = $map->get_hint('dbplug_dbuser');
            $database_pass = $map->get_hint('dbplug_dbpass');
            $database_name = $map->get_hint('dbplug_dbname');
            $database_host = $map->get_hint('dbplug_dbhost');


            try {
                # MySQL with PDO_MYSQL
                $pdo = new PDO("mysql:host=$database_host;dbname=$database_name", $database_user, $database_pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                wm_warn($e->getMessage());
            }

            if ($pdo) {
                $statement = $pdo->prepare("SELECT infield, outfield FROM tablename WHERE host=? LIMIT 1");
                $result = $statement->execute(array($matches[1]));

                if (!$result) {
                    wm_warn("dbsample ReadData: Invalid query: " . $pdo->errorCode() . "\n");
                } else {
                    $row = $statement->fetch(PDO::FETCH_ASSOC);
                    $this->data[IN] = $row['infield'];
                    $this->data[OUT] = $row['outfield'];
                }

                $this->dataTime = time();
            }
        }

        return $this->ReturnData();
    }
}

// vim:ts=4:sw=4:
