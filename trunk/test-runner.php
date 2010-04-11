<?php
    require_once "Weathermap.class.php";

    $version = explode('.', PHP_VERSION);
    $phptag = "php".$version[0];

    $testdir = "test-suite/tests";
    $result1dir = "test-suite/results1-$phptag";
    $result2dir = "test-suite/results2-$phptag";

    $coveragefile = "test-suite/config-coverage.txt";

    if(! file_exists($result1dir)) { mkdir($result1dir); }
    if(! file_exists($result2dir)) { mkdir($result2dir); }

    $dh = opendir($testdir);
    while ($file = readdir($dh)) {
        if(substr($file,-5,5) == '.conf') {

            $imagefile = $file.".png";
            $htmlfile = $file.".html";

            $reference = "test-suite/references/".$file.".png";

#            print "Running test $file to $imagefile/$htmlfile\n";

            if(function_exists("xdebug_get_code_coverage")) {
                xdebug_start_code_coverage();
            }

            TestOutput_RunTest($testdir.DIRECTORY_SEPARATOR.$file, $result1dir.DIRECTORY_SEPARATOR.$imagefile, $result1dir.DIRECTORY_SEPARATOR.$htmlfile, $result1dir.DIRECTORY_SEPARATOR.$file, $coveragefile);

            // now re-read the config we just wrote, so we can make sure
            // it produces the same map.

            TestOutput_RunTest($result1dir.DIRECTORY_SEPARATOR.$file, $result2dir.DIRECTORY_SEPARATOR.$imagefile, $result2dir.DIRECTORY_SEPARATOR.$htmlfile, $result2dir.DIRECTORY_SEPARATOR.$file, $coveragefile);
            
            if(function_exists("xdebug_get_code_coverage")) {
                 // stop collecting coverage information, but don't delete it
                xdebug_stop_code_coverage(FALSE);
            }


            // TODO - add the comparison stuff in here.
            // compare result1 to reference
            // compare result1 to result2
            // for HTML and for PNG file

            $ref_md5 = "fishes";
            if(file_exists($reference)) {
                $ref_md5 = md5_file($reference);
            } else {                
                print "## $file - No reference image\n";
            }
            $gen1_md5 = md5_file($result1dir.DIRECTORY_SEPARATOR.$imagefile);
            $gen2_md5 = md5_file($result2dir.DIRECTORY_SEPARATOR.$imagefile);

            if($gen1_md5 != $gen2_md5) {
                print "## $file - Gen1 & Gen2 image mismatch - WriteConfig failure.\n";
            } else {
                if($ref_md5 != "fishes" && $ref_md5 != $gen2_md5) {
                    print "## $file - Reference & Gen2 image mismatch - Drawing failure.\n";
                }
            }
       }
    }
    closedir($dh);

    if(function_exists("xdebug_get_code_coverage")) {
        ob_start ();
        var_dump(xdebug_get_code_coverage());

        $fd = fopen("test-suite/code-coverage.txt","w+");
        fwrite($fd, ob_get_contents());
        fclose($fd);
        ob_end_clean();
    }

?>
