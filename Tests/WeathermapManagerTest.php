<?php

require_once dirname(__FILE__) . '/../lib/WeathermapManager.class.php';

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

        $weathermap_confdir = realpath(dirname(__FILE__) . '/../configs');


        $this->manager = new WeathermapManager(self::$pdo, $weathermap_confdir);
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

    private function getMapOrder()
    {
        $statement = self::$pdo->prepare("SELECT id FROM weathermap_maps ORDER BY sortorder");
        $statement->execute();
        $order = $statement->fetchAll(PDO::FETCH_NUM);

        $result = array();
        foreach ($order as $suborder) {
            $result [] = $suborder[0];
        }

        return $result;
    }

    private function getGroupOrder()
    {
        $statement = self::$pdo->prepare("SELECT id FROM weathermap_groups ORDER BY sortorder");
        $statement->execute();
        $order = $statement->fetchAll(PDO::FETCH_NUM);

        $result = array();
        foreach ($order as $suborder) {
            $result [] = $suborder[0];
        }

        return $result;
    }


    public function testMoveMapUp()
    {
        $pos = $this->getMapOrder();
        $this->assertEquals($pos, array(7, 6, 5, 4, 1, 2));

        $this->manager->moveMap(6, -1);
        $pos = $this->getMapOrder();
        $this->assertEquals($pos, array(6, 7, 5, 4, 1, 2));

        // it should be at the top now, so this does nothing
        $this->manager->moveMap(6, -1);
        $pos = $this->getMapOrder();
        $this->assertEquals($pos, array(6, 7, 5, 4, 1, 2));

        // and moving back down should work as expected after a failed move up
        $this->manager->moveMap(6, 1);
        $pos = $this->getMapOrder();
        $this->assertEquals($pos, array(7, 6, 5, 4, 1, 2));


    }

    public function testMoveMapDown()
    {

        $pos = $this->getMapOrder();
        $this->assertEquals($pos, array(7, 6, 5, 4, 1, 2));

        $this->manager->moveMap(1, 1);
        $pos = $this->getMapOrder();
        $this->assertEquals($pos, array(7, 6, 5, 4, 2, 1));

        // this should be trying to move the bottom map down, and failing
        $this->manager->moveMap(1, 1);
        $pos = $this->getMapOrder();
        $this->assertEquals($pos, array(7, 6, 5, 4, 2, 1));

        // and moving back up should work as expected after a failed move down
        $this->manager->moveMap(1, -1);
        $pos = $this->getMapOrder();
        $this->assertEquals($pos, array(7, 6, 5, 4, 1, 2));
    }

    public function testDeleteMap()
    {
        $pos = $this->getMapOrder();
        $this->assertEquals($pos, array(7, 6, 5, 4, 1, 2));
        $this->assertEquals(6, $this->getConnection()->getRowCount('weathermap_maps'), "Pre-Condition");
        $this->manager->deleteMap(6);
        $this->assertEquals(5, $this->getConnection()->getRowCount('weathermap_maps'), "Delete failed");
        $pos = $this->getMapOrder();
        $this->assertEquals($pos, array(7, 5, 4, 1, 2));
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
        $pos = $this->getGroupOrder();
        $this->assertEquals($pos, array(2, 1, 3));

        $this->assertEquals(3, $this->getConnection()->getRowCount('weathermap_groups'), "Pre-Condition");
        $this->manager->createGroup("G2");
        $this->assertEquals(4, $this->getConnection()->getRowCount('weathermap_groups'), "Group create failed");

        $pos = $this->getGroupOrder();
        $this->assertEquals($pos, array(2, 1, 3, 4));
    }

    public function testGroupDelete()
    {
        $pos = $this->getGroupOrder();
        $this->assertEquals($pos, array(2, 1, 3));

        $this->assertEquals(3, $this->getConnection()->getRowCount('weathermap_groups'), "Pre-Condition");
        $this->manager->deleteGroup(2);
        $this->assertEquals(2, $this->getConnection()->getRowCount('weathermap_groups'), "Group delete failed");
        $pos = $this->getGroupOrder();
        $this->assertEquals($pos, array(1, 3));

    }

    public function testGroupMove()
    {
        $pos = $this->getGroupOrder();
        $this->assertEquals($pos, array(2, 1, 3));

        $this->manager->moveGroup(1, 1);

        $pos = $this->getGroupOrder();
        $this->assertEquals($pos, array(2, 3, 1));

        $this->manager->moveGroup(3, -1);

        $pos = $this->getGroupOrder();
        $this->assertEquals($pos, array(3, 2, 1));

        $this->manager->moveGroup(3, -1);

        $pos = $this->getGroupOrder();
        $this->assertEquals($pos, array(3, 2, 1));

        $this->manager->moveGroup(1, 1);

        $pos = $this->getGroupOrder();
        $this->assertEquals($pos, array(3, 2, 1));

        $this->manager->moveGroup(1, -1);

        $pos = $this->getGroupOrder();
        $this->assertEquals($pos, array(3, 1, 2));


    }

    public function testAppSettings()
    {
        $this->assertEquals(40, $this->getConnection()->getRowCount('settings'), "Pre-Condition");

        $this->manager->setAppSetting("fish", "trout");
        $this->assertEquals(41, $this->getConnection()->getRowCount('settings'), "Add failed");

        $result = $this->manager->getAppSetting("fish");
        $this->assertEquals("trout", $result);

        $this->manager->setAppSetting("fish", "carp");
        $this->assertEquals(41, $this->getConnection()->getRowCount('settings'), "Update failed");
        $result = $this->manager->getAppSetting("fish");
        $this->assertEquals("carp", $result);


        $result = $this->manager->getAppSetting("dog", "pitbull");
        $this->assertEquals("pitbull", $result);


    }
}
