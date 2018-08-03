<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 04/11/17
 * Time: 20:52
 */

namespace Weathermap\Tests;

use Weathermap\UI\SimpleTemplate;

class SimpleTemplateTest extends \PHPUnit_Framework_TestCase
{
    protected $projectRoot;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->projectRoot = realpath(dirname(__FILE__) . "/../../../");
    }

    public function testBasics()
    {
        $file = $this->projectRoot . "/test-suite/data/test-template.html";

        $t = new SimpleTemplate($file);

        $t->set("title", "STEVE");

        $result = $t->fetch();

        $this->assertEquals("<title>STEVE</title>", $result);
    }
}
