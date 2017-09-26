<?php

namespace Weathermap\Tests;

// Weathermap\UI\wmeSanitize_xxxx all live in here at the moment as loose functions
//require_once dirname(__FILE__) . '/../lib/Weathermap/UI/UIBase.php';

use Weathermap\Editor\EditorUI;
use \Weathermap\UI\UIBase;

class EditorUITest extends \PHPUnit_Framework_TestCase
{

    protected $projectRoot;

    public function setUp()
    {
        $this->projectRoot = realpath(dirname(__FILE__) . "/../../../");
    }

    public function testParameterValidation()
    {
        $ui = new EditorUI();

        $this->assertTrue($ui->validateArgument("int", 10), "Actual integer IS an integer");
        $this->assertTrue($ui->validateArgument("int", "10"), "String containing integer IS an integer");

        $this->assertFalse($ui->validateArgument("int", 10.1));
        $this->assertFalse($ui->validateArgument("int", "10.1"));

        $this->assertFalse($ui->validateArgument("int", "frog"), "String is not an integer");
        $this->assertFalse($ui->validateArgument("int", ""));

        $this->assertTrue($ui->validateArgument("mapfile", "simple.conf"));
        $this->assertFalse($ui->validateArgument("mapfile", "simple.map"));
        $this->assertFalse($ui->validateArgument("mapfile", ".htaccess"));
        $this->assertFalse($ui->validateArgument("mapfile", "index.php"));
        $this->assertFalse($ui->validateArgument("mapfile", "../src/lib/test.conf"));
        $this->assertFalse($ui->validateArgument("mapfile", "../src/lib/test.php"));

        $this->assertFalse($ui->validateArgument("name", "two words"));
        $this->assertTrue($ui->validateArgument("name", "oneword"));
        $this->assertTrue($ui->validateArgument("name", "my_router"));

        $this->assertFalse($ui->validateArgument("jsname", "two words"));
        $this->assertTrue($ui->validateArgument("jsname", "oneword"));
    }

    /**
     * @throws Weathermap\Core\WeathermapInternalFail
     * @expectedException Weathermap\Core\WeathermapInternalFail
     */
    public function testValidateArgsException()
    {
        $ui = new EditorUI();

        // there is no type called 'black'
        $this->assertFalse($ui->validateArgument("black", "white"), "Black is not a recognised type");
    }

    public function testValidCommandMap()
    {
        $ui = new EditorUI();

        foreach ($ui->commands as $action => $command) {
            $this->assertTrue(isset($command['handler']), "Action $action has a handler");
            $handler = $command['handler'];
            $this->assertTrue(
                method_exists($ui, $command['handler']),
                "Action $action has a handler that exists called $handler"
            );
        }
    }

    public function testSanitizers()
    {
        $this->assertEquals("test.conf", UIBase::wmeSanitizeConfigFile("test.conf"));
        $this->assertEquals("te-st.conf", UIBase::wmeSanitizeConfigFile("te-st.conf"));
        $this->assertEquals("", UIBase::wmeSanitizeConfigFile("test"));
        $this->assertEquals("", UIBase::wmeSanitizeConfigFile("test.png"));
        $this->assertEquals("", UIBase::wmeSanitizeConfigFile("index.php"));
        $this->assertEquals("", UIBase::wmeSanitizeConfigFile(".htaccess"));
        $this->assertEquals("", UIBase::wmeSanitizeConfigFile("../../conf/apache.conf"));
        $this->assertEquals("", UIBase::wmeSanitizeConfigFile("../../etc/passwd"));
        $this->assertEquals("", UIBase::wmeSanitizeConfigFile("file*.conf"));

        $this->assertEquals("fish.ext1", UIBase::wmeSanitizeFile("fish.ext1", array("ext1", "ext2")));
        $this->assertEquals("", UIBase::wmeSanitizeFile("fish.ext1", array("ext2", "ext3")));
        $this->assertEquals("", UIBase::wmeSanitizeFile("fish", array("ext2", "ext3")));
    }

    public function testUIInternals()
    {
        $ui = new EditorUI();

        $this->assertEquals(
            "New Title",
            $ui->getTitleFromConfig($this->projectRoot . "/test-suite/tests/conf_title.conf")
        );
        $this->assertEquals(
            "New <b>Title</b>",
            $ui->getTitleFromConfig($this->projectRoot . "/test-suite/tests/conf_title2.conf")
        );
        $this->assertEquals("", $ui->getTitleFromConfig($this->projectRoot . "/test-suite/tests/conf_no_title.conf"));
    }
}
