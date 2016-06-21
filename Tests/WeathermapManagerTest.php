<?php

require_once 'lib/WeathermapManager.class.php';

abstract class Weathermap_DatabaseTestCase extends PHPUnit_Extensions_Database_TestCase
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
}

class WeathermapManagerTest extends PHPUnit_Extensions_Database_TestCase
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

    public function setUp()
    {
        parent::setUp();

        $this->manager = new WeathermapManager(self::$pdo);
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__) . '/../test-suite/data/weathermap-seed.xml');
    }

    public function testAddMap()
    {
        
    }

    public function testMoveMapUp()
    {
        
    }

    public function testMoveMapDown()
    {
        
    }

    public function testDeleteMap()
    {
        $this->assertEquals(6, $this->getConnection()->getRowCount('weathermap_maps'), "Pre-Condition");
        $this->manager->deleteMap(6);
        $this->assertEquals(5, $this->getConnection()->getRowCount('weathermap_maps'), "Delete failed");

    }

    public function testDisableMap()
    {
        $this->manager->disableMap(1);
    }

    public function testActivateMap()
    {
        $this->manager->activateMap(1);
    }

    public function testRenameGroup()
    {
        $this->manager->renameGroup(1, "FISH");
        $group = $this->manager->getGroup(1);
        $this->assertEquals("FISH", $group->name, "Group rename failed");
    }

    public function testGroupCreate()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('weathermap_groups'), "Pre-Condition");
        $this->manager->createGroup("G2");
        $this->assertEquals(3, $this->getConnection()->getRowCount('weathermap_groups'), "Group create failed");
    }

    public function testGroupDelete()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('weathermap_groups'), "Pre-Condition");
        $this->manager->deleteGroup(2);
        $this->assertEquals(1, $this->getConnection()->getRowCount('weathermap_groups'), "Group delete failed");
    }
}
