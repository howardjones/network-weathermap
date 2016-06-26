#!/usr/bin/php
<?php

# 	test-suites/make-failing-summary.php test-suite/failing-images.txt test-suite/summary.html > test-suite/failing-summary.html

$failing_list_file = $argv[1];
$summary_file = $argv[2];

$failcount = 0;

$f = fopen($failing_list_file, "r");

while (!feof($f)) {
    $filename = fgets($f);
    $filename = trim($filename);
    $fails[$filename] = 1;
    $failcount++;
}
fclose($f);

print "<p>$failcount failing</p>";

$f = fopen($summary_file, "r");

$percents = array();

while (!feof($f)) {
    $line = fgets($f);

    if (strstr($line, "<h4>")) {

        $parts = explode(" ", $line);
        $conf = $parts[0];
        $conf = str_replace("<h4>", "", $conf);
        $conf = str_replace("<hr>", "", $conf);

        if ($fails[$conf] == 1) {
            print $line;

            $diff_file = "test-suite/diffs/" . $conf . ".png.txt";
            $reference_file = "test-suite/references/" . $conf . ".png";

            $differences = 0;
            $percent = 0;

            $d = fopen($diff_file, "r");
            while (!feof($d)) {
                $dline = fgets($d);
                if (preg_match("/^Output: \\|(\\d+)/", $dline, $matches)) {
                    $differences = intval($matches[1]);
                }
            }
            fclose($d);

            $dimensions = getimagesize($reference_file);
            $totalpixels = $dimensions[0] * $dimensions[1];

            $percent = sprintf("%.2f%%", $differences / $totalpixels * 100);
            array_push($percents, $percent);

            print sprintf("<p><b>To run:</b><code>./weathermap --config test-suite/tests/%s --debug --no-data</code></p>\n",
                $conf);
            print "<p>$percent - $differences differences.</p>\n";

            print "<a href='approve.php?cf=" . $conf . "'>Approve left image as new reference</a>\n";
            print "<hr>";
        }
    } else {
        print $line;
    }
}

fclose($f);

$c = sizeof($percents);

if ($c > 0) {

    $summary = "";
    $total = array_sum($percents);
    $m = max($percents);

    $summary = sprintf("\n\nAverage %.2f%% Worst %.2f%%\n\n", $total / $c, $m);
    print $summary;

}

error_log($summary);
error_log("$failcount failing");

