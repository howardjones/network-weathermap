<?php

require_once dirname(__FILE__) . '/../lib/all.php';


class DocsTest extends PHPUnit_Framework_TestCase
{
    public function testKeywordCoverage()
    {
        $docsDir = dirname(__FILE__) . "/../docs/src/";
        $docsIndex = $docsDir . "/index.xml";

        $this->assertDirectoryExists($docsDir);
        $this->assertFileExists($docsIndex);

        $map = new WeatherMap();
        $reader = new WeatherMapConfigReader($map);

        $keywords = $reader->getAllKeywords();

        foreach ($keywords as $scope_keyword) {
            $p = explode("_", $scope_keyword);
            $scope = $p[0];
            $keyword = $p[1];

            $file = $docsDir . "/" . $scope_keyword . ".xml";

            // some keywords are not a 1:1 mapping...
            if (substr($scope_keyword, -5, 5) == "_node") {
                $file = $docsDir . "/node_node.xml";
            }
            if (substr($scope_keyword, -5, 5) == "_link") {
                $file = $docsDir . "/link_link.xml";
            }
            if (substr($scope_keyword, -5, 5) == "color") {
                $file = $docsDir . "/".$scope."_colours.xml";
            }
            if ($scope == "global" && substr($scope_keyword, -4, 4) == "font") {
                $file = $docsDir . "/global_font.xml";
            }
            if (substr($scope_keyword, -8, 8) == "_include") {
                $file = $docsDir . "/global_include.xml";
            }

            if ($scope_keyword == "link_maxvalue") {
                $file = $docsDir . "/link_bandwidth.xml";
            }

            $this->assertFileExists($file, "Manual page should exist for $scope_keyword");
        }
    }

    public function testIndexOrphans()
    {
        # Check that all the little config XML files actually appear somewhere in the index.xml

        $docsDir = dirname(__FILE__) . "/../docs/src/";
        $docsIndex = $docsDir . "/index.xml";

        $this->assertDirectoryExists($docsDir);
        $this->assertFileExists($docsIndex);

        $seen = array();

        if ($dh = opendir($docsDir)) {
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
        $this->assertGreaterThan(20, sizeof($seen), "Sensible number of XML docs in docs/src");

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