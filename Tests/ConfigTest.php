<?php
require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__).'/../Weathermap.class.php';

class ConfigTests extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider configlist
     */
    public function testConfigOutput($conffile, $referenceimagefile, $testdir, $result1dir, $result2dir)
    {
        $outputimagefile = $result1dir.DIRECTORY_SEPARATOR.$conffile.".png";
        $outputhtmlfile = $result1dir.DIRECTORY_SEPARATOR.$conffile.".html";

        $previouswd = getcwd();
        chdir(dirname(__FILE__).DIRECTORY_SEPARATOR."..");

        // these are more for test housekeeping than anything else
        $this->assertTrue(file_exists($result1dir));
        $this->assertTrue(is_dir($result1dir));

        $nwarns = TestOutput_RunTest($testdir.DIRECTORY_SEPARATOR.$conffile, $outputimagefile, $outputhtmlfile, '', '');
        
        $this->assertEquals(0, $nwarns, "Warnings were generated");

        $ref_md5 = md5_file($referenceimagefile);
        $ref_output = md5_file($outputimagefile);
        $this->assertEquals($ref_md5, $ref_output, "Output did not match reference");
    
        chdir($previouswd);
    }

   /**
     * @dataProvider configlist
     */
    public function testWriteConfigConsistency($conffile, $referenceimagefile, $testdir, $result1dir, $result2dir)
    {
        $previouswd = getcwd();
        chdir(dirname(__FILE__).DIRECTORY_SEPARATOR."..");

        $outputimagefile = $result1dir.DIRECTORY_SEPARATOR.$conffile.".png";
        $outputimagefile2 = $result2dir.DIRECTORY_SEPARATOR.$conffile.".png";

        $outputconfigfile = $result1dir.DIRECTORY_SEPARATOR.$conffile;

        // these are more for test housekeeping than anything else
        $this->assertTrue(file_exists($result1dir));
        $this->assertTrue(is_dir($result1dir));
        $this->assertTrue(file_exists($result2dir));
        $this->assertTrue(is_dir($result2dir));


        TestOutput_RunTest($testdir.DIRECTORY_SEPARATOR.$conffile, $outputimagefile, '', $outputconfigfile, '');
        TestOutput_RunTest($result1dir.DIRECTORY_SEPARATOR.$conffile, $outputimagefile2, '', '', '');

        $ref_output1 = md5_file($outputimagefile);
        $ref_output2 = md5_file($outputimagefile2);
        $this->assertEquals($ref_output1, $ref_output2,"Output from WriteConfig did not match original");
        chdir($previouswd);

    }

    public function configlist()
    {
        $previouswd = getcwd();
        chdir(dirname(__FILE__).DIRECTORY_SEPARATOR."..");

        $testdir = "test-suite/tests";
        $referencedir = "test-suite/references";

        $version = explode('.', PHP_VERSION);
        $phptag = "php".$version[0];

        $result1dir = "test-suite/results1-$phptag";
        $result2dir = "test-suite/results2-$phptag";

        if(! file_exists($result1dir)) { mkdir($result1dir); }
        if(! file_exists($result2dir)) { mkdir($result2dir); }

        $conflist = array();

        $dh = opendir($testdir);
        while ($file = readdir($dh)) {
            if(substr($file,-5,5) == '.conf') {
                $imagefile = $file.".png";
                $reference = $referencedir."/".$file.".png";

                if(file_exists($reference)) {
                    $conflist[] = array($file, $reference, $testdir, $result1dir, $result2dir);
                }
            }
        }
        closedir($dh);
        chdir($previouswd);

        return $conflist;
    }
}
?>