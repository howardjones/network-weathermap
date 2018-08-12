#!/bin/sh
# Run tests, then produce some summary reports

make test
# grep  Output test-suite/diffs/*.txt | grep -v '|0|' | awk -F: '{ print $1;}' | sed -e 's/.png.txt//' -e 's/test-suite\/diffs\///' > test-suite/failing-images.txt
# test-suite/make-failing-summary.pl test-suite/failing-images.txt test-suite/summary.html > test-suite/summary-failing.html
# php test-suite/make-failing-summary.php test-suite/failing-images.txt test-suite/summary.html > test-suite/summary-failing.html
php test-suite/make-failing-summary.php > test-suite/summary-failing.html
#echo "Creating phpdox"
#vendor/bin/phploc --log-xml=build/phploc.xml lib
#vendor/bin/phpmd  lib xml  cleancode,design,unusedcode > build/pmd.xml
#vendor/bin/phpdox

echo
