<?php

/**
 * Scan through all the .conf files in test-suite/tests and make sure there's a reference image for each.
 * If there isn't, output the appropriate weathermap cli command to generate one.
 */

$testdir = "test-suite/tests/";
$referencedir = "test-suite/references/";

$dh = opendir($testdir);

if ($dh) {
    while (false !== ($file = readdir($dh))) {
        $realfile = $testdir . DIRECTORY_SEPARATOR . $file;
        if (substr($realfile, -5, 5) == ".conf") {
            $referencefile = $referencedir . DIRECTORY_SEPARATOR . $file . ".png";

            if (!file_exists($referencefile)) {
                print "# No reference for $realfile\n";
                printf("./weathermap --config test-suite/tests/$file --output test-suite/references/$file.png\n");
            }
        }
    }
}
