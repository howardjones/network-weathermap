<?php

namespace Weathermap\Tests;

require_once dirname(__FILE__) . '/../../all.php';

use PDO;
use Weathermap\Integrations\Cacti\CactiApplicationInterface;
use Weathermap\Integrations\MapManager;

class MapManagerTest extends \PHPUnit_Extensions_Database_TestCase
{

    // only instantiate pdo once for test clean-up/fixture load
    static private $pdo = null;

    /** @var MapManager */
    private $manager;
    private $projectRoot;
    private $confdir;
    private $testsuite;

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

        $this->projectRoot = realpath(dirname(__FILE__) . "/../../../");

        $this->confdir = $this->projectRoot . '/configs';
        $this->testsuite = $this->projectRoot . "/test-suite";

        $cactiInterface = new CactiApplicationInterface(self::$pdo);
        $this->manager = new MapManager(self::$pdo, $this->confdir, $cactiInterface);
    }

    /**
     * @return \PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(realpath(dirname(__FILE__) . '/weathermap-seed.xml'));
    }

    public function testAddBadMap()
    {
        $pos = $this->getMapOrder();
        $this->assertEquals(array(7, 6, 5, 4, 1, 2), $pos);
        $this->expectException(\Exception::class);
        $this->manager->addMap($this->testsuite . DIRECTORY_SEPARATOR . "tests" . DIRECTORY_SEPARATOR . "simple-node-1.conf");
        $pos = $this->getMapOrder();
        $this->assertEquals(array(7, 6, 5, 4, 1, 2, 8), $pos);
    }

    public function testAddMap()
    {
        $pos = $this->getMapOrder();
        $this->assertEquals(array(7, 6, 5, 4, 1, 2), $pos);
        $this->manager->addMap($this->confdir . DIRECTORY_SEPARATOR . "simple.conf");
        $pos = $this->getMapOrder();
        $this->assertEquals(array(8, 7, 6, 5, 4, 1, 2), $pos);

        $map = $this->manager->getMap(8);

        $this->assertNotNull($map);
        $this->assertInstanceOf(\stdClass::class, $map);

        $this->assertNotEmpty($map->filehash);
        $this->assertNotEmpty($map->titlecache);
        $this->assertNotEmpty($map->group_id);
        $this->assertNotEmpty($map->sortorder);
        $this->assertEquals('on', $map->active);
    }

    public function testMapTitle()
    {
        $file1 = $this->confdir . DIRECTORY_SEPARATOR . "simple.conf";
        $file2 = $this->confdir . DIRECTORY_SEPARATOR . "switch-status.conf";
        $file3 = $this->confdir . DIRECTORY_SEPARATOR . "non-existent.conf";
        $file4 = $this->testsuite . DIRECTORY_SEPARATOR . "tests" . DIRECTORY_SEPARATOR . "conf_titlepos2.conf";

        $this->assertEquals("Simple Map", $this->manager->extractMapTitle($file1));
        $this->assertEquals("(no title)", $this->manager->extractMapTitle($file2));
        $this->assertEquals("(no file)", $this->manager->extractMapTitle($file3));
        $this->assertEquals("New Title", $this->manager->extractMapTitle($file4));
    }

    private function getMapOrder()
    {
        $statement = self::$pdo->prepare("SELECT id FROM weathermap_maps ORDER BY sortorder");
        $statement->execute();
        $order = $statement->fetchAll(PDO::FETCH_NUM);

        $result = array();
        foreach ($order as $suborder) {
            $result [] = intval($suborder[0]);
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

    public function testGetMaps()
    {
        $pos = $this->getMapOrder();
        $this->assertEquals($pos, array(7, 6, 5, 4, 1, 2));

        $maps = $this->manager->getMaps();
        $this->assertEquals(count($pos), count($maps));
        $this->assertInstanceOf(\stdClass::class, $maps[0]);
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

    public function testGroupGet()
    {
        $groups = $this->manager->getGroups();
        $this->assertEquals(3, count($groups));
        $this->assertInstanceOf(\stdClass::class, $groups[0]);
        $this->assertEquals("g1", $groups[0]->name);
        $this->assertEquals(2, $groups[0]->id);
    }

    public function testGetTabs()
    {
        $tabs = $this->manager->getTabs(1);
        $this->assertEquals(1, count($tabs));

        $this->manager->setMapGroup(7, 3);
        $tabs = $this->manager->getTabs(1);
        $this->assertEquals(2, count($tabs));

        $this->manager->setMapGroup(6, 2);
        $tabs = $this->manager->getTabs(1);
        $this->assertEquals(3, count($tabs));

        $tabs = $this->manager->getTabs(2);
        $this->assertEquals(1, count($tabs));

        $tabs = $this->manager->getTabs(3);
        $this->assertEquals(0, count($tabs));
    }

    public function testMapSettings()
    {

        $settings = $this->manager->getMapSettings(0);
        $this->assertEquals(1, count($settings));

        $settings = $this->manager->getMapSettings(-1);
        $this->assertEquals(0, count($settings));

        $settings = $this->manager->getMapSettings(1);
        $this->assertEquals(0, count($settings));

        $this->manager->saveMapSetting(1, "fish", "trout");

        $settings = $this->manager->getMapSettings(1);
        $this->assertEquals(1, count($settings));
        $this->assertEquals("trout", $settings[0]->optvalue);
        $this->assertEquals("fish", $settings[0]->optname);

        $this->manager->updateMapSetting(1, $settings[0]->id, "fish", "carp");

        $settings = $this->manager->getMapSettings(1);
        # print_r($settings);

        $this->assertEquals(1, count($settings));
        $this->assertEquals("carp", $settings[0]->optvalue);
        $this->assertEquals("fish", $settings[0]->optname);

        $deleteId = $settings[0]->id;

        $settings = $this->manager->getAllMapSettings(1);
        $this->assertEquals("carp", $settings->fish->optvalue);

        // Add a group setting for fish too (no change for this map)
        $this->manager->saveMapSetting(-1, "fish", "eel");

        $settings = $this->manager->getAllMapSettings(1);
        $this->assertEquals("carp", $settings->fish->optvalue);

        // delete the map-specific setting, revealing the group setting
        $this->manager->deleteMapSetting(1, $deleteId);

        $settings = $this->manager->getMapSettings(1);
        $this->assertEquals(0, count($settings));

        $settings = $this->manager->getAllMapSettings(1);
        $this->assertEquals("eel", $settings->fish->optvalue);

        $this->manager->saveMapSetting(0, "fish", "halibut");

        $settings = $this->manager->getAllMapSettings(1);
        $this->assertEquals("eel", $settings->fish->optvalue);


        $this->manager->deleteMapSetting(0, $settings->fish->id);

        $settings = $this->manager->getAllMapSettings(1);

        $this->assertEquals("halibut", $settings->fish->optvalue);
    }

    public function testAppSettings()
    {
        $this->assertEquals(40, $this->getConnection()->getRowCount('settings'), "Pre-Condition");

        $this->manager->application->setAppSetting("fish", "trout");
        $this->assertEquals(41, $this->getConnection()->getRowCount('settings'), "Add failed");

        $result = $this->manager->application->getAppSetting("fish");
        $this->assertEquals("trout", $result);

        $this->manager->application->setAppSetting("fish", "carp");
        $this->assertEquals(41, $this->getConnection()->getRowCount('settings'), "Update failed");
        $result = $this->manager->application->getAppSetting("fish");
        $this->assertEquals("carp", $result);


        $result = $this->manager->application->getAppSetting("dog", "pitbull");
        $this->assertEquals("pitbull", $result);

        $this->manager->application->deleteAppSetting("fish");
        $this->assertEquals(40, $this->getConnection()->getRowCount('settings'), "Update failed");
        $result = $this->manager->application->getAppSetting("fish", "tuna");
        $this->assertEquals("tuna", $result);
    }

    public function testCactiUsers()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('user_auth'), "Pre-Condition");

        $users = $this->manager->application->getUserList();
        $this->assertEquals(2, count($users), "via API");

        $users = $this->manager->application->getUserList(true);
        $this->assertEquals(3, count($users), "via API with Anyone");

        $user1 = $users[1];
        $this->assertInstanceOf(\stdClass::class, $user1);
        $this->assertEquals("admin", $user1->username);
        $this->assertEquals(1, $user1->id);
    }

    public function testCactiPerms()
    {
        $users = $this->manager->application->getUserList();
        $this->assertEquals(2, count($users));

        $this->assertTrue($this->manager->application->checkUserAccess(1, 1));
        $this->assertFalse($this->manager->application->checkUserAccess(1, 33));
        $this->assertFalse($this->manager->application->checkUserAccess(19, 1));
    }

    private function objectArrayIncludes($arr, $field, $value)
    {
        foreach ($arr as $obj) {
            if ($obj->$field == $value) {
                return true;
            }
        }
        return false;
    }

    public function testMapPerms()
    {
        $guestUser = 2;
        $mapId = 1;

        $maps = $this->manager->getMapsForUser($guestUser);
        $this->assertEquals(1, count($maps));

        $this->manager->addPermission($mapId, $guestUser);
        $maps = $this->manager->getMapsForUser($guestUser);
        $this->assertEquals(2, count($maps));
        $maps = $this->manager->getMapsForUser($guestUser, 2);
        $this->assertEquals(0, count($maps));


        $users = $this->manager->getMapAuthUsers($mapId);
        $this->assertEquals(2, count($users));
        $this->assertTrue($this->objectArrayIncludes($users, "userid", $guestUser));
        $this->assertTrue($this->objectArrayIncludes($users, "userid", 1));

        $this->manager->removePermission($mapId, $guestUser);
        $maps = $this->manager->getMapsForUser($guestUser);
        $this->assertEquals(1, count($maps));

        $users = $this->manager->getMapAuthUsers($mapId);
        $this->assertEquals(1, count($users));
        $this->assertFalse($this->objectArrayIncludes($users, "userid", $guestUser));
        $this->assertTrue($this->objectArrayIncludes($users, "userid", 1));

        $users = $this->manager->getMapAuth(7);
        $this->assertEquals(2, count($users));
    }

    public function testHash()
    {
        $this->assertEquals(7, $this->manager->translateFileHash("99639caa5ed4ab8ad7a2"));
        $this->assertEquals(7, $this->manager->translateFileHash("switch-status-2.conf"));
    }

    public function testAccess()
    {
        $adminUser = 1;
        $guestUser = 2;
        $nosuchUser = 17;

        $maps = $this->manager->getMapWithAccess($adminUser, 1);
        $this->assertEquals(1, count($maps));

        $maps = $this->manager->getMapWithAccess($guestUser, 1);
        $this->assertEquals(0, count($maps));

        $maps = $this->manager->getMapWithAccess($guestUser, 7);
        $this->assertEquals(1, count($maps));

        $maps = $this->manager->getMapWithAccess($nosuchUser, 1);
        $this->assertEquals(0, count($maps));

        ####

        $maps = $this->manager->getMapsWithAccessAndGroups($adminUser);
        $this->assertEquals(6, count($maps));

        $maps = $this->manager->getMapsWithAccessAndGroups($guestUser);
        $this->assertEquals(1, count($maps));

        $maps = $this->manager->getMapsWithAccessAndGroups($nosuchUser);
        $this->assertEquals(0, count($maps));
    }

    public function testListManagement()
    {
        $count = $this->manager->getMapTotalCount();
        $this->assertEquals(6, $count);

        $maps = $this->manager->getMapsInGroup(1);
        $this->assertEquals(6, count($maps));
        $this->assertInstanceOf(\stdClass::class, $maps[0]);

        $maps = $this->manager->getMapsInGroup(2);
        $this->assertEquals(0, count($maps));


        $maps = $this->manager->getMapsWithGroups();
        $this->assertEquals(6, count($maps));
        $this->assertInstanceOf(\stdClass::class, $maps[0]);
        $this->assertObjectHasAttribute("groupname", $maps[0]);

        $maps = $this->manager->getMapRunList();
        $this->assertEquals(6, count($maps));
        $this->assertInstanceOf(\stdClass::class, $maps[0]);
        // TODO - check ordering!

        $this->manager->disableMap(1);

        $maps = $this->manager->getMapRunList();
        $this->assertEquals(5, count($maps));
        $this->assertInstanceOf(\stdClass::class, $maps[0]);
    }

    public function testDatabaseIntrospection()
    {
        $tableList = $this->manager->getTableList();

        $this->assertEquals(
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
            ),
            $tableList
        );

        $columnList = $this->manager->getColumnList('settings');

        $this->assertEquals(array('name', 'value'), $columnList);


        $columnList = $this->manager->getColumnList('gazorpazorp');

        $this->assertEquals(array(), $columnList);
    }
}
