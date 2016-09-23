#!/usr/bin/php
<?php

# 	test-suites/make-failing-summary.php test-suite/failing-images.txt test-suite/summary.html > test-suite/failing-summary.html

$dir = "test-suite/diffs";
$summary_file = "test-suite/summary.html";

$failcount = 0;
$fails = array();
$different = array();

if (is_dir($dir)) {
    if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
            $file = "$dir/$file";

            if (substr($file, -4, 4) == '.txt') {
                $fd = fopen($file,"r");
                if ($fd) {
                    while (!feof($fd)) {
                        $line = fgets($fd);

                        if (preg_match('/^Output: \|(\d+)\|/', $line, $matches)) {
                            if ($matches[1] != '0') {
                                $realfilename = str_replace(".png.txt", "", $file);
                                $realfilename = str_replace("test-suite/diffs/", "", $realfilename);
                                $fails[$realfilename] = 1;
                                $failcount++;
                                $different[$realfilename] = intval($matches[1]);
                            }
                        }
                    }
                    fclose($fd);
                }
            }
        }
        closedir($dh);
    }
}

$f = fopen($summary_file, "r");

$percents = array();

while (!feof($f)) {
    $line = fgets($f);

    if (strstr($line, "<h4>")) {

        $parts = explode(" ", $line);
        $conf = $parts[0];
        $conf = str_replace("<h4>", "", $conf);
        $conf = str_replace("<hr>", "", $conf);

//        print "$conf\n";

        if (array_key_exists($conf, $fails) && $fails[$conf] == 1) {
            print $line;

            $diff_file = "test-suite/diffs/" . $conf . ".png.txt";
            $reference_file = "test-suite/references/" . $conf . ".png";

            $pixels_different = $different[$conf];
            $percent = 0;

//            $d = fopen($diff_file, "r");
//            while (!feof($d)) {
//                $dline = fgets($d);
//                if (preg_match('/^Output: \\|(\\d+)/', $dline, $matches)) {
//                    $differences = intval($matches[1]);
//                }
//            }
//            fclose($d);

            $dimensions = getimagesize($reference_file);
            $totalpixels = $dimensions[0] * $dimensions[1];

            $percent = sprintf("%.2f%%", $pixels_different / $totalpixels * 100);
            array_push($percents, $percent);

            print sprintf("<p><b>To run:</b><code>./weathermap --config test-suite/tests/%s --debug --no-data</code></p>\n",
                $conf);
            print "<p>$percent - $pixels_different differences.</p>\n";

            print "<a href='approve.php?cf=" . $conf . "'>Approve left image as new reference</a>\n";
            print "<hr>";
        }
    } else {
        print $line;
    }
}

fclose($f);

$c = sizeof($percents);
$summary = "";

if ($c > 0) {
    $total = array_sum($percents);
    $m = max($percents);

    $summary = sprintf("\n\nAverage %.2f%% Worst %.2f%%\n\n", $total / $c, $m);
    print $summary;

}

error_log($summary);
error_log("$failcount failing");

