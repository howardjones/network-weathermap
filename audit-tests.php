<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 26/05/16
 * Time: 20:26
 */

$testdir = "test-suite/tests/";
$referencedir = "test-suite/references/";

$dh=opendir($testdir);

if ($dh) {
    while (false !== ($file = readdir($dh))) {
        $realfile = $testdir . DIRECTORY_SEPARATOR . $file;
        if (substr($realfile,-5,5) == ".conf") {
            $referencefile = $referencedir . DIRECTORY_SEPARATOR . $file . ".png";

            if (! file_exists($referencefile)) {
                print "# No reference for $realfile\n";
                printf("./weathermap --config test-suite/tests/$file --output test-suite/references/$file.png\n");
            }
        }
    }
}