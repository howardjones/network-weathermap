<?php

//require_once dirname(__FILE__) . '/TestSupport.php';

namespace Weathermap\Tests;

//require_once dirname(__FILE__) . '/../lib/all.php';


class ConfigTest extends \PHPUnit_Framework_TestCase
{
    protected static $testsDirectory;
    protected static $result1Directory;
    protected static $result2Directory;
    protected static $referenceDirectory;
    protected static $osSpecificReferenceDirectory;
    protected static $diffsDirectory;
    protected static $phpTag;
    protected static $osTag;

    protected static $previousWorkingDirectory;
    protected static $compare;

    protected static $testSuiteDirectory;
//    protected static $confdir;

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
        $outputImageFileName = self::$result1Directory . DIRECTORY_SEPARATOR . $configFileName . ".png";
        $comparisonImageFileName = self::$diffsDirectory . DIRECTORY_SEPARATOR . $configFileName . ".png";
        $outputHTMLFileName = self::$result1Directory . DIRECTORY_SEPARATOR . $configFileName . ".html";

        $compareOutputFileName = $comparisonImageFileName . ".txt";
        if (file_exists($compareOutputFileName)) {
            unlink($compareOutputFileName);
        }

        $this->assertFileExists($referenceImageFileName, "reference image $referenceImageFileName missing");

        $warningCount = TestSupport::runOutputTest(
            self::$testsDirectory . DIRECTORY_SEPARATOR . $configFileName,
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
        $outputImageFileName1 = self::$result1Directory . DIRECTORY_SEPARATOR . $configFileName . ".png";
        $outputImageFileName2 = self::$result2Directory . DIRECTORY_SEPARATOR . $configFileName . ".png";

        $outputConfigFileName = self::$result1Directory . DIRECTORY_SEPARATOR . $configFileName;

        TestSupport::runOutputTest(
            self::$testsDirectory . DIRECTORY_SEPARATOR . $configFileName,
            $outputImageFileName1,
            '',
            $outputConfigFileName
        );
        TestSupport::runOutputTest(
            self::$result1Directory . DIRECTORY_SEPARATOR . $configFileName,
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

        $summaryFileName = self::$testSuiteDirectory . DIRECTORY_SEPARATOR . "summary.html";
        $osTag = self::$osTag;
        $fileHandle = fopen($summaryFileName, "w");
        if ($fileHandle === false) {
            throw new \Exception("Failed to open summary file: $summaryFileName");
        }
        fputs(
            $fileHandle,
            "<html><head><title>Test summary for $osTag</title><style>img {border: 1px solid black; }</style></head><body><h3>Test Summary for $osTag</h3>(result - reference - diff)<br/>\n"
        );
        fputs($fileHandle, "<p>" . date("Y-m-d H:i:s") . "</p>\n");

        $configList = array();

        $testFiles = array();
        if (is_dir(self::$testsDirectory)) {
            $dh = opendir(self::$testsDirectory);

            while (false !== ($entry = readdir($dh))) {
                $testFiles[] = $entry;
            }
            sort($testFiles);
            closedir($dh);
        } else {
            throw new Exception("Test directory " . self::$testsDirectory . " doesn't exist!");
        }

        foreach ($testFiles as $file) {
            if (substr($file, -5, 5) == '.conf') {
                $resultURL = "results1-" . self::$phpTag . "/" . $file . ".png";
                $diffURL = "diffs-" . self::$phpTag . "/" . $file . ".png";
                $referenceURL = "references/" . $file . ".png";

                $reference = self::$referenceDirectory . DIRECTORY_SEPARATOR . $file . ".png";

                if (file_exists(self::$osSpecificReferenceDirectory . DIRECTORY_SEPARATOR . $file . ".png")) {
                    $reference = self::$osSpecificReferenceDirectory . DIRECTORY_SEPARATOR . $file . ".png";
                    $referenceURL = "references/" . self::$osTag . "/" . $file . ".png";
                }

                $configList[$file] = array($file, $reference);

                $title = TestSupport::getMapTitle(self::$testsDirectory . DIRECTORY_SEPARATOR . $file);

                fputs(
                    $fileHandle,
                    sprintf(
                        "<h4>%s <a href=\"tests/%s\">[conf]</a> <em>%s</em></h4><p><nobr>Out:<img align=middle src='%s'> Ref:<img src='%s' align=middle> Diff:<img align=middle src='%s'></nobr></p>\n",
                        $file,
                        $file,
                        htmlspecialchars($title),
                        $resultURL,
                        $referenceURL,
                        $diffURL
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
        chdir(self::$previousWorkingDirectory);
    }

    protected function setUp()
    {
        global $WEATHERMAP_DEBUGGING;

        parent::setUp();

        $this->projectRoot = realpath(dirname(__FILE__) . "/../../../");

        // sometimes useful to figure out what's going on!
        $WEATHERMAP_DEBUGGING = false;

        self::$previousWorkingDirectory = getcwd();
        chdir($this->projectRoot);
    }

    private function generateSlug($str)
    {
        # special accents
        return strtolower(preg_replace(array('/[^a-zA-Z0-9 -]/', '/[ -]+/', '/^-|-$/'), array('', '-', ''), $str));
    }

    // this has to happen before the dataprovider runs, but
    // setUp and setUpBeforeClass are both called *after* the dataprovider
    private function checkPaths()
    {
        $version = explode('.', PHP_VERSION);
        self::$phpTag = "php-" . $version[0] . "." . $version[1];

        $testSuiteRoot = realpath(dirname(__FILE__) . "/../../../") . "/test-suite";

//        self::$phpTag = $phpTag;
        self::$result1Directory = $testSuiteRoot . DIRECTORY_SEPARATOR . "results1-" . self::$phpTag;
        self::$result2Directory = $testSuiteRoot . DIRECTORY_SEPARATOR . "results2-" . self::$phpTag;
        self::$diffsDirectory = $testSuiteRoot . DIRECTORY_SEPARATOR . "diffs-" . self::$phpTag;

        self::$testsDirectory = $testSuiteRoot . DIRECTORY_SEPARATOR . "tests";
        self::$referenceDirectory = $testSuiteRoot . DIRECTORY_SEPARATOR . "references";
        self::$osSpecificReferenceDirectory = "";

        $osCodename = trim(shell_exec("lsb_release -c -s"));
        if ($osCodename == "") {
            $osCodename = $this->generateSlug(PHP_OS);
        }
//        $osTag = $osCodename . "-" . self::$phpTag;
        self::$osTag = $osCodename . "-" . self::$phpTag;

        $osSpecificReferences = $testSuiteRoot . DIRECTORY_SEPARATOR . "references/" . self::$osTag;
        if (is_dir($osSpecificReferences)) {
            self::$osSpecificReferenceDirectory = $osSpecificReferences;
        }

        self::$testSuiteDirectory = $testSuiteRoot;

        if (!file_exists(self::$result1Directory)) {
            mkdir(self::$result1Directory);
        }
        if (!file_exists(self::$result2Directory)) {
            mkdir(self::$result2Directory);
        }
        if (!file_exists(self::$diffsDirectory)) {
            mkdir(self::$diffsDirectory);
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
