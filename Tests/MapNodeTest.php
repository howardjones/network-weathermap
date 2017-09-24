<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 24/09/17
 * Time: 18:38
 */

use Weathermap\Core\Map;
use Weathermap\Core\MapNode;

class MapNodeTest extends PHPUnit_Framework_TestCase
{
    public function testInheritedFieldList()
    {
        $map = new Map();

        $node = new MapNode("test", ":: DEFAULT ::", $map);
        $node->selfValidate();
    }
}
