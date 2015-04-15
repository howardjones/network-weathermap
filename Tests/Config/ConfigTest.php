<?php
// require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__).'/../../lib/all.php';

class ConfigTest extends PHPUnit_Framework_TestCase
{
    protected static $testdir;
    protected static $result1dir;
    protected static $result2dir;
    protected static $referencedir;
    protected static $diffdir;
    protected static $phptag;

    protected static $previouswd;
    protected static $compare;

    /**
     * @dataProvider configlist
     */
    public function testConfigOutput($conffile, $referenceimagefile)
    {
        $outputimagefile = self::$result1dir . DIRECTORY_SEPARATOR . $conffile . ".png";
        $comparisonimagefile = self::$diffdir . DIRECTORY_SEPARATOR . $conffile . ".png";
        $outputhtmlfile = self::$result1dir . DIRECTORY_SEPARATOR . $conffile . ".html";

        $compare_output = $comparisonimagefile . ".txt";
        if (file_exists($compare_output)) {
            unlink($compare_output);
        }

        $nwarns = TestOutput_RunTest(self::$testdir . DIRECTORY_SEPARATOR . $conffile, $outputimagefile, $outputhtmlfile, '', '');

        // if REQUIRES_VERSION was set, and set to a newer version, then this test is known to fail
        if ($nwarns < 0) {
            $this->markTestIncomplete('This test is for a future feature');
            chdir($previouswd);
            return;
        }

        $this->assertEquals(0, $nwarns, "Warnings were generated");

        # $COMPARE -metric AE $reference $result $destination  > $destination2 2>&1
        $cmd = sprintf("%s -metric AE \"%s\" \"%s\" \"%s\"",
            self::$compare,
            $referenceimagefile,
            $outputimagefile,
            $comparisonimagefile
        );

        if (file_exists(self::$compare)) {

            $fd2 = fopen($comparisonimagefile . ".txt", "w");
            fwrite($fd2, $cmd . "\r\n\r\n");
            fwrite($fd2, getcwd() . "\r\n\r\n");

            # $fd = popen($cmd,"r");
            $descriptorspec = array(
                0 => array("pipe", "r"), // stdin is a pipe that the child will read from
                1 => array("pipe", "w"), // stdout is a pipe that the child will write to
                2 => array("pipe", "w") // stderr is a pipe to write to
            );
            $pipes = array();
            $process = proc_open($cmd, $descriptorspec, $pipes, getcwd(), NULL, array('bypass_shell' => TRUE));
            $output = "";

            if (is_resource($process)) {
                $output = fread($pipes[2], 2000);
                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $return_value = proc_close($process);
                fwrite($fd2, "Returned $return_value\r\n");
                fwrite($fd2, "Output: |" . $output . "|\r\n");
            }

            fclose($fd2);

            // it turns out that some versions of compare output two lines, and some don't, so trim.
            $lines = explode("\n", $output);
            $output = $lines[0];

            $this->AssertEquals("0", $output, "Image Output did not match reference for $conffile via IM");

        }
    }

    /**
     * @dataProvider configlist
     */
    public function testWriteConfigConsistency($conffile, $referenceimagefile)
    {
       # return;

        $output_image_round1 = self::$result1dir . DIRECTORY_SEPARATOR . $conffile . ".png";
        $output_image_round2 = self::$result2dir . DIRECTORY_SEPARATOR . $conffile . ".png";

        $outputconfigfile = self::$result1dir . DIRECTORY_SEPARATOR . $conffile;

        TestOutput_RunTest(self::$testdir . DIRECTORY_SEPARATOR . $conffile, $output_image_round1, '', $outputconfigfile, '');
        TestOutput_RunTest(self::$result1dir . DIRECTORY_SEPARATOR . $conffile, $output_image_round2, '', '', '');

        $ref_output1 = md5_file($output_image_round1);
        $ref_output2 = md5_file($output_image_round2);

        $this->assertEquals($ref_output1, $ref_output2, "Config Output from WriteConfig did not match original for $conffile");
    }

    public function configlist()
    {
        self::checkPaths();

        $fd = fopen("test-suite/summary.html", "w");
        fputs($fd, "<html><head><title>Test summary</title><style>img {border: 1px solid black; }</style></head><body><h3>Test Summary</h3>(result - reference - diff)<br/>\n");

        $conflist = array();
        $files = array();

        if (is_dir(self::$testdir)) {
            $dh = opendir(self::$testdir);
            $files = array();
            while ($files[] = readdir($dh)) ;
            sort($files);
            closedir($dh);
        } else {
           # die("Test directory ".self::$testdir." doesn't exist!");
        }

        foreach ($files as $file) {
            if (substr($file, -5, 5) == '.conf') {
                $imagefile = $file . ".png";
                $reference = self::$referencedir . DIRECTORY_SEPARATOR . $file . ".png";

                if (file_exists($reference)) {
                    $conflist[] = array($file, $reference);

                    $title = get_map_title(self::$testdir . DIRECTORY_SEPARATOR . $file);

                    fputs($fd, sprintf("<h4>%s <a href=\"tests/%s\">[conf]</a> <em>%s</em></h4><p><nobr>Out:<img align=middle src='results1-%s/%s.png'> Ref:<img src='references/%s.png' align=middle> Diff:<img align=middle src='diffs/%s.png'></nobr></p>\n",
                        $file, $file, htmlspecialchars($title),
                        self::$phptag, $file,
                        $file,
                        $file
                    ));

                }
            }
        }

        fputs($fd, "</body></html>");
        fclose($fd);

        return $conflist;
    }

    protected function tearDown()
    {
        chdir(self::$previouswd);
    }

    protected function setUp()
    {
        self::$previouswd = getcwd();
        chdir(dirname(__FILE__) . DIRECTORY_SEPARATOR . "../..");
    }

    // this has to happen before the dataprovider runs, but
    // setUp and setUpBeforeClass are both called *after* the dataprovider
    function checkPaths()
    {
        $version = explode('.', PHP_VERSION);
        $phptag = "php".$version[0];

        self::$phptag = "php".$version[0];
        self::$result1dir = "test-suite".DIRECTORY_SEPARATOR."results1-$phptag";
        self::$result2dir = "test-suite".DIRECTORY_SEPARATOR."results2-$phptag";
        self::$diffdir = "test-suite".DIRECTORY_SEPARATOR."diffs";

        self::$testdir = "test-suite".DIRECTORY_SEPARATOR."tests";
        self::$referencedir = "test-suite".DIRECTORY_SEPARATOR."references";

        if (! file_exists(self::$result1dir)) {
            mkdir(self::$result1dir);
        }
        if (! file_exists(self::$result2dir)) {
            mkdir(self::$result2dir);
        }
        if (! file_exists(self::$diffdir)) {
            mkdir(self::$diffdir);
        }

        self::$compare = null;
        // NOTE: This path will change between systems...
        $compares = array(
            "/usr/bin/compare",
            "/usr/local/bin/compare",
            "test-suite".DIRECTORY_SEPARATOR."tools".DIRECTORY_SEPARATOR."compare.exe"
        );

        foreach ($compares as $c) {
            if(file_exists($c) and is_executable($c)) {
                self::$compare = $c;
                break;
            }
        }

        if( ! file_exists(self::$compare)) {
            die("Compare path doesn't exist (or isn't executable) - do you have Imagemagick? \n");
        }
    }


}

function get_map_title($realfile) {
	$title = "";
	$fd=fopen($realfile, "r");
	if ($fd) {
		while (!feof($fd)) {
			$buffer=fgets($fd, 1024);
	
			if (preg_match("/^\s*TITLE\s+(.*)/i", $buffer, $matches)) {
				$title= $matches[1];
				break;
			}
		}
	
		fclose ($fd);
	}
	return $title;
}

