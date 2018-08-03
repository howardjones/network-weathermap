<?php

//require_once dirname(__FILE__) . '/TestSupport.php';

namespace Weathermap\Tests;

//require_once dirname(__FILE__) . '/../lib/all.php';


class ConfigTest extends \PHPUnit_Framework_TestCase
{
    protected static $testdir;
    protected static $result1dir;
    protected static $result2dir;
    protected static $referencedir;
    protected static $os_referencedir;
    protected static $diffdir;
    protected static $php_tag;
    protected static $os_tag;

    protected static $previouswd;
    protected static $compare;

    protected static $testsuite;
    protected static $confdir;

    protected $projectRoot;


    /**
     * Read a config file, write an image.
     * Compare the image to the 'reference' image that we generated when the
     * test config was originally written. Because different PNG/gd versions
     * actually produce different bytes for the same image, we use ImageMagick's
     * 'compare' to do this. This also produces a nice visual diff image.
     *
     * Currently this test does most of the heavy lifting for our code coverage.
     * Each test config should have a small scope of tested features if possible, or
     * a specific previous bug to avoid regressions.
     *
     * @param string $configFileName config file to run
     * @param string $referenceImageFileName image file with expected output
     *
     * @dataProvider configlist
     */
    public function testConfigOutput($configFileName, $referenceImageFileName)
    {
        $outputImageFileName = self::$result1dir . DIRECTORY_SEPARATOR . $configFileName . ".png";
        $comparisonImageFileName = self::$diffdir . DIRECTORY_SEPARATOR . $configFileName . ".png";
        $outputHTMLFileName = self::$result1dir . DIRECTORY_SEPARATOR . $configFileName . ".html";

        $compareOutputFileName = $comparisonImageFileName . ".txt";
        if (file_exists($compareOutputFileName)) {
            unlink($compareOutputFileName);
        }

        $this->assertFileExists($referenceImageFileName, "reference image $referenceImageFileName missing");

        $warningCount = TestSupport::runOutputTest(
            self::$testdir . DIRECTORY_SEPARATOR . $configFileName,
            $outputImageFileName,
            $outputHTMLFileName,
            ''
        );

        // if REQUIRES_VERSION was set, and set to a newer version, then this test is known to fail
        if ($warningCount < 0) {
            $this->markTestIncomplete('This test is for a future feature');
            // chdir($previouswd);
            return;
        }

        $this->assertEquals(0, $warningCount, "Warnings were generated");

        # $COMPARE -metric AE $reference $result $destination  > $destination2 2>&1
        $cmd = sprintf(
            "%s -metric AE -dissimilarity-threshold 1 \"%s\" \"%s\" \"%s\"",
            self::$compare,
            $referenceImageFileName,
            $outputImageFileName,
            $comparisonImageFileName
        );

        if (file_exists(self::$compare)) {
            $fd2 = fopen($comparisonImageFileName . ".txt", "w");
            fwrite($fd2, $cmd . "\r\n\r\n");
            fwrite($fd2, getcwd() . "\r\n\r\n");

            $descriptorSpec = array(
                0 => array("pipe", "r"), // stdin is a pipe that the child will read from
                1 => array("pipe", "w"), // stdout is a pipe that the child will write to
                2 => array("pipe", "w") // stderr is a pipe to write to
            );
            $pipes = array();
            $process = proc_open($cmd, $descriptorSpec, $pipes, getcwd(), null, array('bypass_shell' => true));
            $output = "";

            if (is_resource($process)) {
                $output = fread($pipes[2], 2000);
                $output = trim($output); // newer imagemagick versions add a CRLF to the output
                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $returnValue = proc_close($process);
                fwrite($fd2, "Returned $returnValue\r\n");
                fwrite($fd2, "Output: |" . $output . "|\r\n");
            }

            fclose($fd2);

            // it turns out that some versions of compare output two lines, and some don't, so trim.
            $lines = explode("\n", $output);


            $this->AssertEquals("0", $output, "Image Output did not match reference for $configFileName via IM");
        }
    }

    /**
     * Read a config, write an image, write a config (an editor cycle)
     * then read *that* config, write an image.
     * then compare the two output images - they should be identical
     * (both are written by the same GD/png combo, so they should be
     * byte-identical this time)
     *
     * @param string $configFileName config file to run
     * @param string $referenceImageFileName reference image (not used, just here because we share a dataprovider)
     *
     * @dataProvider configlist
     */
    public function testWriteConfigConsistency($configFileName, $referenceImageFileName)
    {
        $outputImageFileName1 = self::$result1dir . DIRECTORY_SEPARATOR . $configFileName . ".png";
        $outputImageFileName2 = self::$result2dir . DIRECTORY_SEPARATOR . $configFileName . ".png";

        $outputConfigFileName = self::$result1dir . DIRECTORY_SEPARATOR . $configFileName;

        TestSupport::runOutputTest(
            self::$testdir . DIRECTORY_SEPARATOR . $configFileName,
            $outputImageFileName1,
            '',
            $outputConfigFileName
        );
        TestSupport::runOutputTest(
            self::$result1dir . DIRECTORY_SEPARATOR . $configFileName,
            $outputImageFileName2,
            '',
            ''
        );

        $imageFileHash1 = md5_file($outputImageFileName1);
        $imageFileHash2 = md5_file($outputImageFileName2);

        $this->assertEquals(
            $imageFileHash1,
            $imageFileHash2,
            "Config Output from WriteConfig did not match original for $configFileName"
        );
    }

    public function configlist()
    {
        self::checkPaths();

        $summaryFileName = self::$testsuite . DIRECTORY_SEPARATOR . "summary.html";
        $fileHandle = fopen($summaryFileName, "w");
        if ($fileHandle === false) {
            throw new \Exception("Failed to open summary file: $summaryFileName");
        }
        fputs(
            $fileHandle,
            "<html><head><title>Test summary</title><style>img {border: 1px solid black; }</style></head><body><h3>Test Summary</h3>(result - reference - diff)<br/>\n"
        );
        fputs($fileHandle, "<p>" . date("Y-m-d H:i:s") . "</p>\n");

        $configList = array();

        $testFiles = array();
        if (is_dir(self::$testdir)) {
            $dh = opendir(self::$testdir);

            while (false !== ($entry = readdir($dh))) {
                $testFiles[] = $entry;
            }
            sort($testFiles);
            closedir($dh);
        } else {
            throw new Exception("Test directory " . self::$testdir . " doesn't exist!");
        }

        foreach ($testFiles as $file) {
            if (substr($file, -5, 5) == '.conf') {

                $result_url = "results1-" . self::$php_tag . "/" . $file . ".png";
                $diff_url = "diffs-" . self::$php_tag . "/" . $file . ".png";
                $reference_url = "references/" . $file . ".png";

                $reference = self::$referencedir . DIRECTORY_SEPARATOR . $file . ".png";

                if (file_exists(self::$os_referencedir . DIRECTORY_SEPARATOR . $file . ".png")) {
                    $reference = self::$os_referencedir . DIRECTORY_SEPARATOR . $file . ".png";
                    $reference_url = "references/" . self::$os_tag . "/" . $file . ".png";
                }

                $configList[$file] = array($file, $reference);

                $title = TestSupport::getMapTitle(self::$testdir . DIRECTORY_SEPARATOR . $file);

                fputs(
                    $fileHandle,
                    sprintf(
                        "<h4>%s <a href=\"tests/%s\">[conf]</a> <em>%s</em></h4><p><nobr>Out:<img align=middle src='%s'> Ref:<img src='%s' align=middle> Diff:<img align=middle src='%s'></nobr></p>\n",
                        $file,
                        $file,
                        htmlspecialchars($title),
                        $result_url,
                        $reference_url,
                        $diff_url
                    )
                );
            }
        }

        fputs($fileHandle, "</body></html>");
        fclose($fileHandle);

        return $configList;
    }

    protected function tearDown()
    {
        chdir(self::$previouswd);
    }

    protected function setUp()
    {
        global $WEATHERMAP_DEBUGGING;

        parent::setUp();

        $this->projectRoot = realpath(dirname(__FILE__) . "/../../../");

        // sometimes useful to figure out what's going on!
        $WEATHERMAP_DEBUGGING = false;

        self::$previouswd = getcwd();
        chdir($this->projectRoot);
    }

    // this has to happen before the dataprovider runs, but
    // setUp and setUpBeforeClass are both called *after* the dataprovider
    private function checkPaths()
    {
        $version = explode('.', PHP_VERSION);
        $php_tag = "php-" . $version[0] . "." . $version[1];

//        $here = dirname(__FILE__);
        $testSuiteRoot = realpath(dirname(__FILE__) . "/../../../") . "/test-suite";
//        $testSuiteRoot = $here . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "test-suite";

        self::$php_tag = $php_tag;
        self::$result1dir = $testSuiteRoot . DIRECTORY_SEPARATOR . "results1-$php_tag";
        self::$result2dir = $testSuiteRoot . DIRECTORY_SEPARATOR . "results2-$php_tag";
        self::$diffdir = $testSuiteRoot . DIRECTORY_SEPARATOR . "diffs-$php_tag";

        self::$testdir = $testSuiteRoot . DIRECTORY_SEPARATOR . "tests";
        self::$referencedir = $testSuiteRoot . DIRECTORY_SEPARATOR . "references";
        self::$os_referencedir = "";

        $os_codename = trim(shell_exec("lsb_release -c -s"));
        $os_tag = "$os_codename-$php_tag";

        self::$os_tag = $os_tag;

        $os_references = $testSuiteRoot . DIRECTORY_SEPARATOR . "references/$os_tag";
        if (is_dir($os_references)) {
            self::$os_referencedir = $os_references;
        }

        self::$testsuite = $testSuiteRoot;

        if (!file_exists(self::$result1dir)) {
            mkdir(self::$result1dir);
        }
        if (!file_exists(self::$result2dir)) {
            mkdir(self::$result2dir);
        }
        if (!file_exists(self::$diffdir)) {
            mkdir(self::$diffdir);
        }

        self::$compare = null;
        // NOTE: This path will change between systems...
        $compares = array(
            "/usr/bin/compare",
            "/usr/local/bin/compare",
            $testSuiteRoot . DIRECTORY_SEPARATOR . "tools" . DIRECTORY_SEPARATOR . "compare.exe"
        );

        foreach ($compares as $c) {
            if (file_exists($c) and is_executable($c)) {
                self::$compare = $c;
                break;
            }
        }

        if (!file_exists(self::$compare)) {
            throw new \Exception("Compare path doesn't exist (or isn't executable) - do you have Imagemagick? \n");
        }
    }
}
