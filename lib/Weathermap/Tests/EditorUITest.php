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

        $this->assertFalse($ui->validateArgument("", 10), "Blank type is always false");

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

        $this->assertTrue($ui->validateArgument("bool", "1"));
        $this->assertTrue($ui->validateArgument("bool", "0"));
        $this->assertFalse($ui->validateArgument("bool", ""));
        $this->assertFalse($ui->validateArgument("bool", "true"));
        $this->assertFalse($ui->validateArgument("bool", "yes"));

        $this->assertTrue($ui->validateArgument("item_type", "node"));
        $this->assertTrue($ui->validateArgument("item_type", "link"));
        $this->assertFalse($ui->validateArgument("item_type", "fish"));

        $this->assertTrue($ui->validateArgument("maphash", "00000000000000000000"));
        $this->assertTrue($ui->validateArgument("maphash", "12d0efb546f0c038079a"));
        $this->assertFalse($ui->validateArgument("maphash", "node"));
        $this->assertFalse($ui->validateArgument("maphash", "0000000000000000000Z"));
        $this->assertFalse($ui->validateArgument("maphash", "0000000000f0000000a"));
        $this->assertFalse($ui->validateArgument("maphash", "00000000000f000000000a"));


        $this->assertTrue($ui->validateArgument("string", ""));
        $this->assertTrue($ui->validateArgument("string", "sdfsdf"));

        $this->assertFalse($ui->validateArgument("non-empty-string", ""));
        $this->assertTrue($ui->validateArgument("non-empty-string", "sdfsdf"));


        $this->assertTrue($ui::wmeValidateOneOf("fish", array("fish", "cat", "dog")));
        $this->assertTrue($ui::wmeValidateOneOf("fish", array("FISH", "cat", "dog")));
        $this->assertTrue($ui::wmeValidateOneOf("fish", array("dog", "cat", "fish")));
        $this->assertTrue($ui::wmeValidateOneOf("fish", array("cat", "fish", "dog")));
        $this->assertTrue($ui::wmeValidateOneOf("fish", array("cat", "fisH", "dog")));
        $this->assertFalse($ui::wmeValidateOneOf("fish", array("cat", "snake", "dog")));

        $this->assertFalse($ui::wmeValidateOneOf("fish", array("FISH", "cat", "dog"), true));
        $this->assertFalse($ui::wmeValidateOneOf("fish", array("dog", "cat", "Fish"), true));
        $this->assertFalse($ui::wmeValidateOneOf("fish", array("cat", "fisH", "dog"), true));
        $this->assertFalse($ui::wmeValidateOneOf("fish", array("cat", "snake", "dog"), true));
        $this->assertTrue($ui::wmeValidateOneOf("fish", array("fish", "snake", "dog"), true));

        $this->assertTrue($ui::wmeValidateBandwidth("1234"));
        $this->assertTrue($ui::wmeValidateBandwidth("1234.4"));
        $this->assertTrue($ui::wmeValidateBandwidth("1234K"));
        $this->assertTrue($ui::wmeValidateBandwidth("1234.4K"));
        $this->assertTrue($ui::wmeValidateBandwidth("1234M"));
        $this->assertTrue($ui::wmeValidateBandwidth("1234.4M"));
        $this->assertTrue($ui::wmeValidateBandwidth("1234G"));
        $this->assertTrue($ui::wmeValidateBandwidth("1234.4G"));
        $this->assertTrue($ui::wmeValidateBandwidth("1234T"));
        $this->assertTrue($ui::wmeValidateBandwidth("1234.4T"));

        $this->assertFalse($ui::wmeValidateBandwidth("M"));
        $this->assertFalse($ui::wmeValidateBandwidth("123 M"));
        $this->assertFalse($ui::wmeValidateBandwidth("1.2.3M"));
        $this->assertTrue($ui::wmeValidateBandwidth("1.M"));
        $this->assertFalse($ui::wmeValidateBandwidth(".1M"));
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
        $this->assertEquals("", UIBase::wmeSanitizeConfigFile(""));
        $this->assertEquals("", UIBase::wmeSanitizeConfigFile("test.png"));
        $this->assertEquals("", UIBase::wmeSanitizeConfigFile("index.php"));
        $this->assertEquals("", UIBase::wmeSanitizeConfigFile(".htaccess"));
        $this->assertEquals("", UIBase::wmeSanitizeConfigFile("../../conf/apache.conf"));
        $this->assertEquals("", UIBase::wmeSanitizeConfigFile("../../etc/passwd"));
        $this->assertEquals("", UIBase::wmeSanitizeConfigFile("file*.conf"));
        $this->assertEquals("", UIBase::wmeSanitizeConfigFile("file?.conf"));

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
