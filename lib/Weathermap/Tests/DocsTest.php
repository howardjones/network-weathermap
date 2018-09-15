<?php

namespace Weathermap\Tests;

//require_once dirname(__FILE__) . '/../lib/all.php';

use Weathermap\Core\Map;
use Weathermap\Core\ConfigReader;

class DocsTest extends \PHPUnit_Framework_TestCase
{
    protected $projectRoot;
    protected $docsRoot;

    protected function setUp()
    {
        $this->projectRoot = realpath(dirname(__FILE__) . "/../../../");
        $this->docsRoot = $this->projectRoot . "/docs/src/";
    }

    public function testKeywordCoverage()
    {

        $docsIndex = $this->docsRoot . "/index.xml";

        $this->assertDirectoryExists($this->docsRoot);
        $this->assertFileExists($docsIndex);

        $map = new Map();
        $reader = new ConfigReader($map);

        $keywords = $reader->getAllKeywords();

        foreach ($keywords as $scopeKeyword) {
            $p = explode("_", $scopeKeyword);
            $scope = $p[0];
//            $keyword = $p[1];

            $file = $this->docsRoot . "/" . $scopeKeyword . ".xml";

            // some keywords are not a 1:1 mapping...
            if (substr($scopeKeyword, -5, 5) == "_node") {
                $file = $this->docsRoot . "/node_node.xml";
            }
            if (substr($scopeKeyword, -5, 5) == "_link") {
                $file = $this->docsRoot . "/link_link.xml";
            }
            if (substr($scopeKeyword, -5, 5) == "color") {
                $file = $this->docsRoot . "/" . $scope . "_colours.xml";
            }
            if ($scope == "global" && substr($scopeKeyword, -4, 4) == "font") {
                $file = $this->docsRoot . "/global_font.xml";
            }
            if (substr($scopeKeyword, -8, 8) == "_include") {
                $file = $this->docsRoot . "/global_include.xml";
            }

            if ($scopeKeyword == "link_maxvalue") {
                $file = $this->docsRoot . "/link_bandwidth.xml";
            }

            $this->assertFileExists($file, "Manual page should exist for $scopeKeyword");
        }
    }

    public function testIndexOrphans()
    {
        # Check that all the little config XML files actually appear somewhere in the index.xml
        $docsIndex = $this->docsRoot . "/index.xml";

        $this->assertDirectoryExists($this->docsRoot);
        $this->assertFileExists($docsIndex);

        $seen = array();

        if ($dh = opendir($this->docsRoot)) {
            while (($file = readdir($dh)) !== false) {
                if (substr($file, -4, 4) == ".xml") {
                    if (preg_match('/^(node|global|link)_/', $file)) {
                        $seen[$file] = 0;
                    }
                }
            }
            closedir($dh);
        }
        # This number is just made up, but to catch the case where the test is looking in the wrong place
        $this->assertGreaterThan(20, count($seen), "Sensible number of XML docs in docs/src");

        $fd = fopen($docsIndex, "r");
        while (!feof($fd)) {
            $line = fgets($fd);
            trim($line);
            if (preg_match('/xi:include\s+href\s*=\s*"([^"]+)"/', $line, $matches)) {
                $target = $matches[1];
                if ($target != "contents.xml") {
                    $this->assertArrayHasKey($target, $seen, "File $target in index.xml doesn't exist");
                    $seen[$target] = 1;
                }
            }
        }
        fclose($fd);

        foreach ($seen as $keyword => $count) {
            $this->assertEquals(1, $count, "File $keyword wasn't mentioned in index.xml");
        }
    }
}
