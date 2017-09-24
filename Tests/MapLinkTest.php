<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 24/09/17
 * Time: 18:38
 */

use Weathermap\Core\MapLink;
use Weathermap\Core\Map;

class MapLinkTest extends PHPUnit_Framework_TestCase
{

    public function testInheritedFieldList()
    {
        $map = new Map();

        $link = new MapLink("test", ":: DEFAULT ::", $map);
        $link->selfValidate();
    }
    
}
