#!/usr/bin/php
<?php
#
/***
 * Usage: test-suites/make-failing-summary.php test-suite/failing-images.txt test-suite/summary.html > test-suite/failing-summary.html
 *
 * Take the summary data produced by ConfigTest, and generate an HTML report, showing
 * the reference, the output from pass 1 and the diff image from ImageMagick.
 */

print "Creating summary info from ConfigTest failures...\n";

$version = explode('.', PHP_VERSION);
$php_tag = "php-" . $version[0] . "." . $version[1];

$dir = "test-suite/diffs-" . $php_tag;
$summaryFile = "test-suite/summary.html";

$failCount = 0;
$fails = array();
$different = array();

if (is_dir($dir)) {
    if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
            $file = "$dir/$file";

            if (substr($file, -4, 4) == '.txt') {
                $fd = fopen($file, "r");
                if ($fd) {
                    while (!feof($fd)) {
                        $line = fgets($fd);

                        if (preg_match('/^Output: \|(\d+)\|/', $line, $matches)) {
                            if ($matches[1] != '0') {
                                $realfilename = str_replace(".png.txt", "", $file);
                                $realfilename = str_replace($dir . "/", "", $realfilename);
                                $fails[$realfilename] = 1;
                                $failCount++;
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

$f = fopen($summaryFile, "r");

$percents = array();

while (!feof($f)) {
    $line = fgets($f);

    if (strstr($line, "<h4>")) {
        $parts = explode(" ", $line);
        $conf = $parts[0];
        $conf = str_replace("<h4>", "", $conf);
        $conf = str_replace("<hr>", "", $conf);

        if (array_key_exists($conf, $fails) && $fails[$conf] == 1) {
            print $line;

            $diffFile = $dir . "/" . $conf . ".png.txt";
            $referenceFile = "test-suite/references/" . $conf . ".png";

            $pixelsDifferent = $different[$conf];
            $percent = 0;

            $dimensions = getimagesize($referenceFile);
            $totalPixels = $dimensions[0] * $dimensions[1];

            $percent = sprintf("%.2f%%", $pixelsDifferent / $totalPixels * 100);
            array_push($percents, $percent);

            print sprintf(
                "<p><b>To run:</b><code>./weathermap --config test-suite/tests/%s --debug --no-data</code></p>\n",
                $conf
            );
            print "<p>$percent - $pixelsDifferent differences.</p>\n";

            print "<a href='approve.php?cf=" . $conf . "'>Approve left image as new reference</a>\n";
            print "<hr>";
        }
    } else {
        print $line;
    }
}
print "<hr>";
fclose($f);

$c = count($percents);
$summary = "";

if ($c > 0) {
    $total = array_sum($percents);
    $m = max($percents);

    $summary = sprintf("\n\nAverage %.2f%% Worst %.2f%%\n\n", $total / $c, $m);
    print $summary;

}

error_log($summary);
error_log("$failCount failing");
