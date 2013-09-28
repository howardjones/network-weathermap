<?php
// require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__).'/../lib/Weathermap.class.php';

class ConfigTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider configlist
     */
    public function testConfigOutput($conffile, $referenceimagefile, $testdir, $result1dir, $result2dir, $diffdir, $compare)
    {
        $outputimagefile = $result1dir.DIRECTORY_SEPARATOR.$conffile.".png";
        $comparisonimagefile = $diffdir.DIRECTORY_SEPARATOR.$conffile.".png";
        $outputhtmlfile = $result1dir.DIRECTORY_SEPARATOR.$conffile.".html";

        $previouswd = getcwd();
        chdir(dirname(__FILE__).DIRECTORY_SEPARATOR."..");

	$compare_output = $comparisonimagefile.".txt";
	if(file_exists($compare_output)) {
	    unlink($compare_output);
	}
		
        $nwarns = TestOutput_RunTest($testdir.DIRECTORY_SEPARATOR.$conffile, $outputimagefile, $outputhtmlfile, '', 'config-coverage.txt');

		// if REQUIRES_VERSION was set, and set to a newer version, then this test is known to fail
		if($nwarns < 0) {
			$this->markTestIncomplete( 'This test is for a future feature');
				chdir($previouswd);
			return;
		}
			
        $this->assertEquals(0, $nwarns, "Warnings were generated");

        
		 # $COMPARE -metric AE $reference $result $destination  > $destination2 2>&1
		$cmd = sprintf("%s -metric AE \"%s\" \"%s\" \"%s\"",
				$compare,
				$referenceimagefile,
				$outputimagefile,
				$comparisonimagefile
				);

		if(file_exists($compare)) {

			$fd2 = fopen($comparisonimagefile.".txt","w");
			fwrite($fd2,$cmd."\r\n\r\n");
			fwrite($fd2,getcwd()."\r\n\r\n");

			# $fd = popen($cmd,"r");
			$descriptorspec = array(
			   0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
			   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
			   2 => array("pipe", "w")  // stderr is a pipe to write to
			);
			$pipes = array();
			$process = proc_open($cmd,$descriptorspec, $pipes, getcwd(), NULL, array('bypass_shell'=>TRUE)) ;
			$output = "";

			if(is_resource($process)) {
				$output = fread($pipes[2],2000);
				fclose($pipes[0]);
				fclose($pipes[1]);
				fclose($pipes[2]);
				$return_value = proc_close($process);
				fwrite($fd2, "Returned $return_value\r\n");
				fwrite($fd2,"Output: |".$output."|\r\n");
			}

			fclose($fd2);

		// it turns out that some versions of compare output two lines, and some don't, so trim.
		$lines = explode("\n",$output);
		$output = $lines[0];

                $this->AssertEquals("0", $output, "Image Output did not match reference for $conffile via IM");

            }
        
        
        $ref_md5 = md5_file($referenceimagefile);
        $output_md5 = md5_file($outputimagefile);
        $this->assertEquals($ref_md5, $output_md5, "Image Output did not match reference for $conffile via MD5");

//        $this->assertFileEquals($referenceimagefile, $outputimagefile, "Output did not match reference for $conffile");

        chdir($previouswd);
    }

   /**
     * @dataProvider configlist
     */
    public function testWriteConfigConsistency($conffile, $referenceimagefile, $testdir, $result1dir, $result2dir, $diffdir, $compare)
    {
        $previouswd = getcwd();
        chdir(dirname(__FILE__).DIRECTORY_SEPARATOR."..");

        $outputimagefile = $result1dir.DIRECTORY_SEPARATOR.$conffile.".png";
        $outputimagefile2 = $result2dir.DIRECTORY_SEPARATOR.$conffile.".png";

        $outputconfigfile = $result1dir.DIRECTORY_SEPARATOR.$conffile;

        TestOutput_RunTest($testdir.DIRECTORY_SEPARATOR.$conffile, $outputimagefile, '', $outputconfigfile, '');
        TestOutput_RunTest($result1dir.DIRECTORY_SEPARATOR.$conffile, $outputimagefile2, '', '', '');

        $ref_output1 = md5_file($outputimagefile);
        $ref_output2 = md5_file($outputimagefile2);
        $this->assertEquals($ref_output1, $ref_output2,"Config Output from WriteConfig did not match original for $conffile");
//        $this->assertFileEquals($outputimagefile, $outputimagefile2, "Output did not match reference for $conffile");

        chdir($previouswd);

    }

    public function configlist()
    {
        $previouswd = getcwd();
        chdir(dirname(__FILE__).DIRECTORY_SEPARATOR."..");

        $testdir = "test-suite".DIRECTORY_SEPARATOR."tests";
        $referencedir = "test-suite".DIRECTORY_SEPARATOR."references";

        $version = explode('.', PHP_VERSION);
        $phptag = "php".$version[0];

        $result1dir = "test-suite".DIRECTORY_SEPARATOR."results1-$phptag";
        $result2dir = "test-suite".DIRECTORY_SEPARATOR."results2-$phptag";
        $diffdir = "test-suite".DIRECTORY_SEPARATOR."diffs";
      
        // NOTE: This path will change between systems...
        $compare = "test-suite".DIRECTORY_SEPARATOR."tools".DIRECTORY_SEPARATOR."compare.exe";
        $compare = "/usr/bin/compare";
        $compare = "/usr/local/bin/compare";
        
        if( ! file_exists($compare)) {
            die("Compare path doesn't exist.");
        }
        
        if(! file_exists($result1dir)) { mkdir($result1dir); }
        if(! file_exists($result2dir)) { mkdir($result2dir); }
        if(! file_exists($diffdir)) { mkdir($diffdir); }
        
        $fd = fopen("test-suite/summary.html","w");
        fputs($fd,"<html><head><title>Test summary</title><style>img {border: 1px solid black; }</style></head><body><h3>Test Summary</h3>(result - reference - diff)<br/>\n");
        
        $conflist = array();

        $dh = opendir($testdir);
        while ($file = readdir($dh)) {
            if(substr($file,-5,5) == '.conf') {
                $imagefile = $file.".png";
                $reference = $referencedir.DIRECTORY_SEPARATOR.$file.".png";

                if(file_exists($reference)) {
                    $conflist[] = array($file, $reference, $testdir, $result1dir, $result2dir, $diffdir, $compare);
                
                    $title = get_map_title($testdir.DIRECTORY_SEPARATOR.$file);
                    
                    fputs($fd,sprintf("<h4>%s <a href=\"tests/%s\">[conf]</a> <em>%s</em></h4><p><nobr>Out:<img align=middle src='results1-%s/%s.png'> Ref:<img src='references/%s.png' align=middle> Diff:<img align=middle src='diffs/%s.png'></nobr></p>\n",
                    		$file, $file, htmlspecialchars($title),
                    		$phptag, $file,
                    		$file,
                    		$file
                    ));                    
                	
                }
            }
        }
        closedir($dh);
        chdir($previouswd);

	fputs($fd,"</body></html>");
        fclose($fd);
 
        return $conflist;
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
?>
