<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 24/09/17
 * Time: 18:38
 */

namespace Weathermap\Tests;

use Weathermap\Core\Map;

class HTMLTest extends \PHPUnit_Framework_TestCase
{

    public function testNotes()
    {
        $testSuiteRoot = realpath(dirname(__FILE__) . "/../../../") . "/test-suite";

        $map = new Map();
        $map->readConfig($testSuiteRoot . "/tests/link-notes-1.conf");
        $map->drawMap('null');

        $htmlfile = $testSuiteRoot . "/tmp-link-1.html";

        $fd = fopen($htmlfile, 'w');
        fwrite($fd, $map->makeHTML());
        fclose($fd);

        # check HTML output is correct

        $dom = new \DomDocument;

        $dom->loadHTMLFile($htmlfile);
        $xpath = new \DomXPath($dom);

//        $l1 = $map->getLink('l1');
//        print_r($l1->imagemapAreas);

        $res = $xpath->query('//area');
        $this->assertEquals(10, $res->length, '10 areas. 3 links with 2 areas each (2 arrows) and 2 bwlabels for l2, and one each for timestamp and title)');

        $res = $xpath->query('//area[@id="LINK:L109:0"]/@onmouseover');
        $this->assertEquals(1, $res->length, "There should be exactly one AREA");
        $this->assertContains("Note 2", $res[0]->nodeValue);

        $res = $xpath->query('//area[@id="LINK:L109:2"]/@onmouseover');
        $this->assertEquals(1, $res->length, "There should be exactly one AREA");
        $this->assertContains("Note 2", $res[0]->nodeValue);

        $res = $xpath->query('//area[@id="LINK:L108:0"]/@onmouseover');
        $this->assertEquals(1, $res->length, "There should be exactly one AREA");
        $this->assertContains("Note 1", $res[0]->nodeValue);


        $res = $xpath->query('//area[@id="LINK:L110:0"]/@onmouseover');
        $this->assertEquals(1, $res->length);
        $this->assertContains("Note 3 with <b>HTML</b>", $res[0]->nodeValue);

        $res = $xpath->query('//area[@id="LINK:L110:1"]/@onmouseover');
        $this->assertEquals(1, $res->length);
        $this->assertContains("Note 3 with <b>HTML</b>", $res[0]->nodeValue);



        $map = new Map();
        $map->readConfig($testSuiteRoot . "/tests/link-notes-2.conf");
        $map->drawMap('null');

        $htmlfile = $testSuiteRoot . "/tmp-link-2.html";

        $fd = fopen($htmlfile, 'w');
        fwrite($fd, $map->makeHTML());
        fclose($fd);

        $dom = new \DomDocument;

        $dom->loadHTMLFile($htmlfile);
        $xpath = new \DomXPath($dom);

        $res = $xpath->query('//area');
        $this->assertEquals(8, $res->length);

        $res = $xpath->query('//area[@id="LINK:L108:1"]/@onmouseover');
        $this->assertEquals(1, $res->length, "There should be exactly one AREA");
        $this->assertContains("Note 1 out", $res[0]->nodeValue);

        $res = $xpath->query('//area[@id="LINK:L108:0"]/@onmouseover');
        $this->assertEquals(1, $res->length, "There should be exactly one AREA");
        $this->assertContains("Note 1 in", $res[0]->nodeValue);

        $res = $xpath->query('//area[@id="LINK:L110:0"]/@onmouseover');
        $this->assertEquals(1, $res->length, "There should be exactly one AREA");
        $this->assertContains("Note 3 In with <b>HTML</b>", $res[0]->nodeValue);

        $res = $xpath->query('//area[@id="LINK:L110:1"]/@onmouseover');
        $this->assertEquals(1, $res->length, "There should be exactly one AREA");
        $this->assertContains("overlib(''", $res[0]->nodeValue);

        $res = $xpath->query('//area[@id="LINK:L109:1"]/@onmouseover');
        $this->assertEquals(1, $res->length, "There should be exactly one AREA");
        $this->assertContains("'Note 2'", $res[0]->nodeValue);

        $res = $xpath->query('//area[@id="LINK:L109:0"]/@onmouseover');
        $this->assertEquals(1, $res->length, "There should be exactly one AREA");
        $this->assertContains("'Note 2 In'", $res[0]->nodeValue);
    }
}
