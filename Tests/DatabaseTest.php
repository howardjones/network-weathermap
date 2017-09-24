<?php


require_once dirname(__FILE__) . '/../lib/Weathermap/Integrations/Cacti/database.php';


class DatabaseTest extends PHPUnit_Extensions_Database_TestCase
{

    // only instantiate pdo once for test clean-up/fixture load
    static private $pdo = null;

    // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
    private $conn = null;

    final public function getConnection()
    {
        if ($this->conn === null) {
            if (self::$pdo == null) {
                self::$pdo = new PDO($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);
            }
            $this->conn = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_DBNAME']);
        }

        return $this->conn;
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__) . '/../test-suite/data/weathermap-seed.xml');
    }

    public function testListTables()
    {
        $table_list = weathermap_get_table_list(self::$pdo);

        $this->assertEquals($table_list, array(
            'settings',
            'user_auth',
            'user_auth_perms',
            'user_auth_realm',
            'weathermap_auth',
            'weathermap_data',
            'weathermap_groups',
            'weathermap_maps',
            'weathermap_settings'
        ));

    }
}
