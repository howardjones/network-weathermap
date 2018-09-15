<?php


namespace Weathermap\Tests;

require_once dirname(__FILE__) . '/../Integrations/Cacti/database.php';

// the cacti config variables
$database_default = null;
$database_username = null;
$database_password = null;
$database_hostname = null;
$database_type = null;

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
        global $database_type, $database_default, $database_hostname, $database_username, $database_password;

        $this->projectRoot = realpath(dirname(__FILE__) . "/../../../");

        $database_default = $GLOBALS['DB_DBNAME'];
        $database_username = $GLOBALS['DB_USER'];
        $database_password = $GLOBALS['DB_PASSWD'];
        $database_hostname = 'localhost';
        $database_type = "mysqli";
    }

    public function getConnection()
    {
        $pdo = new PDO($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);

        return $this->createDefaultDBConnection($pdo, $GLOBALS['DB_DBNAME']);
    }

// FIXME This test only works when embedded in a Cacti installation

//    public function testGetPDO()
//    {
//        $pdo = weathermap_get_pdo();
//
//        $this->assertInstanceOf("PDO", $pdo);
//    }

    /**
     * @return \PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(realpath(dirname(__FILE__) . '/weathermap-seed.xml'));
    }
}
