<?php

namespace Weathermap\Tests;

require_once dirname(__FILE__) . '/../Integrations/Cacti/database.php';

use PDO;
use PHPUnit_Extensions_Database_TestCase;

class DatabaseTest extends PHPUnit_Extensions_Database_TestCase
{

    // only instantiate pdo once for test clean-up/fixture load
    static private $pdo = null;

    // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
    private $conn = null;

    protected $projectRoot;

    public function setUp()
    {
        $this->projectRoot = realpath(dirname(__FILE__) . "/../../../");
    }

    public function getConnection()
    {

        $pdo = new PDO($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);

        return $this->createDefaultDBConnection($pdo, $GLOBALS['DB_DBNAME']);
    }

    /**
     * @return \PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(realpath(dirname(__FILE__) . '/weathermap-seed.xml'));
    }

    public function testListTables()
    {
//        $this->assertNotNull(self::$pdo, "PDO is initialized");

        $tableList = weathermap_get_table_list(self::$pdo);

        $this->assertEquals(
            $tableList,
            array(
                'settings',
                'user_auth',
                'user_auth_perms',
                'user_auth_realm',
                'weathermap_auth',
                'weathermap_data',
                'weathermap_groups',
                'weathermap_maps',
                'weathermap_settings'
            )
        );
    }
}
