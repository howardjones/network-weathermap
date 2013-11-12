grep  Output test-suite/diffs/*.txt | grep -v '|0' | awk -F: '{ print $1;}' | sed -e 's/.png.txt//' -e 's/test-suite\/diffs\///' > test-suite/failing-images.txt
test-suite/make-failing-summary.pl test-suite/failing-images.txt test-suite/summary.html > test-suite/summary-failing.html

phpmd lib/ html unusedcode > test-suite/md-unused.html
phpmd lib/ html codesize,design,naming,cleancode > test-suite/md-rest.html

