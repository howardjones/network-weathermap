<?php

//require_once dirname(__FILE__) . '/../lib/all.php';
//require_once dirname(__FILE__) . '/../lib/EditorUI.php';

use Weathermap\Editor\EditorUI;


class Editor16UITest extends PHPUnit_Framework_TestCase
{
    public function testInternals()
    {
        $ui = new EditorUI();

        $this->assertTrue($ui->selfValidate());
    }
}