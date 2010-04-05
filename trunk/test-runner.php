<?php
    require_once "Weathermap.class.php";

    chdir("test-suite");

    $version = explode('.', PHP_VERSION);
    $phptag = "php".$version[0];

    $testdir = "tests";
    $result1dir = "results1-$phptag";
    $result2dir = "results2-$phptag";

    $coveragefile = "coverage.txt";

    if(! file_exists($result1dir)) { mkdir($result1dir); }
    if(! file_exists($result2dir)) { mkdir($result2dir); }

    $dh = opendir($testdir);
    while ($file = readdir($dh)) {
        if(substr($file,-5,5) == '.conf') {

            $imagefile = str_replace(".conf",".png", $file);
            $htmlfile = str_replace(".conf",".html", $file);

            print "Running test $file to $imagefile/$htmlfile\n";

            $map = new WeatherMap();
            $map->SeedCoverage();
            if(file_exists($coveragefile) ) {
                $map->LoadCoverage($coveragefile);
            }

            $map->ReadConfig($testdir.DIRECTORY_SEPARATOR.$file);
            $map->ReadData();
            $map->DrawMap($result1dir.DIRECTORY_SEPARATOR.$imagefile);
            $map->imagefile=$result1dir.DIRECTORY_SEPARATOR.$imagefile;
            HTML_output($result1dir.DIRECTORY_SEPARATOR.$htmlfile, $map);
            $map->WriteConfig($result1dir.DIRECTORY_SEPARATOR.$file);
            $map->SaveCoverage($coveragefile);
        
            $map->CleanUp();
            unset ($map);

            // now re-read the config we just wrote, so we can make sure
            // it produces the same map.

            $map2 = new WeatherMap();
            $map2->ReadConfig($result1dir.DIRECTORY_SEPARATOR.$file);
            $map2->ReadData();
            $map2->DrawMap($result2dir.DIRECTORY_SEPARATOR.$imagefile);
            $map2->imagefile=$result2dir.DIRECTORY_SEPARATOR.$imagefile;
            HTML_output($result1dir.DIRECTORY_SEPARATOR.$htmlfile, $map2);
            $map2->CleanUp();
            unset ($map2);

            // TODO - add the comparison stuff in here.
            // compare result1 to reference
            // compare result1 to result2
            // for HTML and for PNG file



       }
    }
    closedir($dh);


    function HTML_output($htmlfile, &$map)
    {
        global $WEATHERMAP_VERSION;

        $fd=fopen($htmlfile, 'w');
        fwrite($fd,
                '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head>');
        if($map->htmlstylesheet != '') fwrite($fd,'<link rel="stylesheet" type="text/css" href="'.$map->htmlstylesheet.'" />');
        fwrite($fd,'<meta http-equiv="refresh" content="300" /><title>' . $map->ProcessString($map->title, $map) . '</title></head><body>');

        if ($map->htmlstyle == "overlib")
        {
                fwrite($fd,
                        "<div id=\"overDiv\" style=\"position:absolute; visibility:hidden; z-index:1000;\"></div>\n");
                fwrite($fd,
                        "<script type=\"text/javascript\" src=\"overlib.js\"><!-- overLIB (c) Erik Bosrup --></script> \n");
        }

        fwrite($fd, $map->MakeHTML());
        fwrite($fd,
                '<hr /><span id="byline">Network Map created with <a href="http://www.network-weathermap.com/?vs='
                . $WEATHERMAP_VERSION . '">PHP Network Weathermap v' . $WEATHERMAP_VERSION
                . '</a></span></body></html>');
        fclose ($fd);
    }

    ?>
