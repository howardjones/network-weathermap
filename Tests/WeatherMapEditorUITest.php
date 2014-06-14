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

}
 