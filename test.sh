make test
  grep  Output test-suite/diffs/*.txt | grep -v '|0|' | awk -F: '{ print $1;}' | sed -e 's/.png.txt//' -e 's/test-suite\/diffs\///' > test-suite/failing-images.txt
        test-suite/make-failing-summary.pl test-suite/failing-images.txt test-suite/summary.html > test-suite/summary-failing.html

echo
