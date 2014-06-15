<?php

require_once dirname(__FILE__).'/../lib/all.php';
require_once dirname(__FILE__).'/../lib/WeatherMapEditor.class.php';
require_once dirname(__FILE__).'/../lib/WeatherMapEditorUI.class.php';

class WeatherMapEditorUITest extends PHPUnit_Framework_TestCase {
    function testParameterValidation()
    {
        $ui = new WeatherMapEditorUI();

        $this->assertTrue($ui->validateArgument("int", 10),"Actual integer IS an integer");
        $this->assertTrue($ui->validateArgument("int", "10"),"String containing integer IS an integer");

        $this->assertFalse($ui->validateArgument("int", 10.1));
        $this->assertFalse($ui->validateArgument("int", "10.1"));

        $this->assertFalse($ui->validateArgument("int", "frog"),"String is not an integer");
        $this->assertFalse($ui->validateArgument("int", ""));

        $this->assertTrue($ui->validateArgument("mapfile", "simple.conf"));
        $this->assertFalse($ui->validateArgument("mapfile", "simple.map"));
        $this->assertFalse($ui->validateArgument("mapfile", ".htaccess"));
        $this->assertFalse($ui->validateArgument("mapfile", "index.php"));
        $this->assertFalse($ui->validateArgument("mapfile", "../lib/test.conf"));
        $this->assertFalse($ui->validateArgument("mapfile", "../lib/test.php"));

        $this->assertFalse($ui->validateArgument("name", "two words"));
        $this->assertTrue($ui->validateArgument("name", "oneword"));
        $this->assertTrue($ui->validateArgument("name", "my_router"));

        $this->assertFalse($ui->validateArgument("jsname", "two words"));
        $this->assertTrue($ui->validateArgument("jsname", "oneword"));

        // there is no type called 'black'
        $this->assertFalse($ui->validateArgument("black", "white"),"Black is not a recognised type");
    }

    function testValidCommandMap()
    {
        $ui = new WeatherMapEditorUI();

        foreach ($ui->commands as $action => $command) {
            $this->assertTrue(isset($command['handler']), "Action $action has a handler");
            $this->assertTrue(method_exists($ui, $command['handler']), "Action $action has a handler that exists");
        }
    }

    function testSanitizers()
    {
        $this->assertEquals( "test.conf", wmeSanitizeConfigFile("test.conf"));
        $this->assertEquals( "te-st.conf", wmeSanitizeConfigFile("te-st.conf"));
        $this->assertEquals( "", wmeSanitizeConfigFile("test"));
        $this->assertEquals( "", wmeSanitizeConfigFile("test.png"));
        $this->assertEquals( "", wmeSanitizeConfigFile("index.php"));
        $this->assertEquals( "", wmeSanitizeConfigFile(".htaccess"));
        $this->assertEquals( "", wmeSanitizeConfigFile("../../conf/apache.conf"));
        $this->assertEquals( "", wmeSanitizeConfigFile("../../etc/passwd"));
        $this->assertEquals( "", wmeSanitizeConfigFile("file*.conf"));

        $this->assertEquals( "fish.ext1", wmeSanitizeFile("fish.ext1",array("ext1","ext2")));
        $this->assertEquals( "", wmeSanitizeFile("fish.ext1",array("ext2","ext3")));
    }

    function testUIInternals()
    {
        $ui = new WeatherMapEditorUI();

        $this->assertEquals("New Title", $ui->getTitleFromConfig("test-suite/tests/conf_title.conf"));
        $this->assertEquals("New <b>Title</b>", $ui->getTitleFromConfig("test-suite/tests/conf_title2.conf"));
        $this->assertEquals("", $ui->getTitleFromConfig("test-suite/tests/conf_no_title.conf"));
    }
}
 