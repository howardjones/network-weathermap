<?php
require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__).'/../Weathermap.class.php';

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

        $nwarns = TestOutput_RunTest($testdir.DIRECTORY_SEPARATOR.$conffile, $outputimagefile, $outputhtmlfile, '', 'config-coverage.txt');
        
        $this->assertEquals(0, $nwarns, "Warnings were generated");

         # $COMPARE -metric AE $reference $result $destination  > $destination2 2>&1
        $cmd = sprintf("%s -metric AE \"%s\" \"%s\" \"%s\"",
                $compare,
                $referenceimagefile,
                $outputimagefile,
                $comparisonimagefile
                );
//      print "|$cmd|\n";

        if(file_exists($compare)) {
            // system($cmd);
            $fd = popen($cmd,"r");
            $output = fread($fd,2000);
            pclose($fd);
            print "\n$cmd [$output]\n";
            
            $this->AssertEquals($output, "0", "Image Output did not match reference for $conffile via IM");
            
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

        $compare = "test-suite".DIRECTORY_SEPARATOR."tools".DIRECTORY_SEPARATOR."compare.exe";

        if(! file_exists($result1dir)) { mkdir($result1dir); }
        if(! file_exists($result2dir)) { mkdir($result2dir); }
        if(! file_exists($diffdir)) { mkdir($diffdir); }

        $conflist = array();

        $dh = opendir($testdir);
        while ($file = readdir($dh)) {
            if(substr($file,-5,5) == '.conf') {
                $imagefile = $file.".png";
                $reference = $referencedir.DIRECTORY_SEPARATOR.$file.".png";

                if(file_exists($reference)) {
                    $conflist[] = array($file, $reference, $testdir, $result1dir, $result2dir, $diffdir, $compare);
                }
            }
        }
        closedir($dh);
        chdir($previouswd);

        return $conflist;
    }
}
?>
