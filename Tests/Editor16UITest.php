<?php

require_once dirname(__FILE__) . '/../lib/all.php';
require_once dirname(__FILE__).'/../lib/WeatherMapEditorUI.class.php';


class Editor16UITest extends PHPUnit_Framework_TestCase
{
    public function testInternals()
    {
        $ui = new WeatherMapEditorUI();

        $this->assertTrue($ui->selfValidate());
    }
}